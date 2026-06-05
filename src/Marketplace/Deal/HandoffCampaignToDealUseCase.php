<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Deal;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Marketplace\DealOpportunity;
use NeneServe\Marketplace\PdoAdvertiserRepository;
use NeneServe\Marketplace\PdoCampaignRepository;
use NeneServe\Marketplace\PdoDealOpportunityRepository;
use NeneServe\Marketplace\UseCase\CampaignNotFoundException;
use NeneServe\Marketplace\UseCase\DealHandoffFailedException;
use NeneServe\Tenant\AuthContext;
use NeneServe\Upstream\Deal\DealClientException;
use NeneServe\Upstream\Deal\DealClientInterface;

/**
 * Hands a campaign off to NeNe Deal as an opportunity. Idempotent (one
 * opportunity per campaign), failure-isolated: a transport failure records a
 * `failed` opportunity (audited) and surfaces 502 — serving is unaffected and
 * the call is retryable (ADR 0002).
 */
final readonly class HandoffCampaignToDealUseCase implements HandoffCampaignToDealUseCaseInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private DatabaseTransactionManagerInterface $transactions,
        private DealClientInterface $deal,
    ) {
    }

    public function execute(AuthContext $actor, string $campaignId): DealOpportunity
    {
        $campaign = (new PdoCampaignRepository($this->query))->findByIdInOrganization($campaignId, $actor->organizationId);

        if ($campaign === null) {
            throw new CampaignNotFoundException();
        }

        $externalReference = sprintf('deal:%s:%s', $actor->organizationId, $campaign->id);

        $existing = (new PdoDealOpportunityRepository($this->query))->findByExternalReference($actor->organizationId, $externalReference);

        if ($existing !== null && $existing->status === 'sent') {
            return $existing; // idempotent: no duplicate opportunity
        }

        $advertiser = (new PdoAdvertiserRepository($this->query))->findByIdInOrganization($campaign->advertiserId, $actor->organizationId);
        $advertiserName = $advertiser !== null ? $advertiser->name : $campaign->advertiserId;

        $pending = new DealOpportunity(
            'do-' . bin2hex(random_bytes(8)),
            $actor->organizationId,
            $campaign->id,
            $externalReference,
            $campaign->budgetCents,
            'pending',
            null,
            gmdate('c'),
        );

        try {
            $result = $this->deal->createOpportunity($externalReference, $advertiserName, $campaign->name, $campaign->budgetCents);
        } catch (DealClientException $e) {
            $failed = $pending->withResult('failed', null);
            $this->transactions->transactional(
                static function (DatabaseQueryExecutorInterface $tx) use ($failed, $actor): void {
                    (new PdoDealOpportunityRepository($tx))->save($failed);
                    (new PdoAuditLog($tx))->record(
                        $actor->organizationId,
                        $actor->userId,
                        'deal.opportunity_failed',
                        'campaign',
                        $failed->campaignId,
                        ['external_reference' => $failed->externalReference],
                    );
                },
            );

            throw new DealHandoffFailedException('Deal handoff failed: ' . $e->getMessage(), 0, $e);
        }

        $sent = $pending->withResult('sent', $result->opportunityId);

        return $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($sent, $actor): DealOpportunity {
                (new PdoDealOpportunityRepository($tx))->save($sent);
                (new PdoAuditLog($tx))->record(
                    $actor->organizationId,
                    $actor->userId,
                    'deal.opportunity_sent',
                    'campaign',
                    $sent->campaignId,
                    ['external_reference' => $sent->externalReference, 'opportunity_id' => $sent->opportunityId, 'amount_cents' => $sent->amountCents],
                );

                return $sent;
            },
        );
    }
}
