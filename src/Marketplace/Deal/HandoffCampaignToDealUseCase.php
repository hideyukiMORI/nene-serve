<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Deal;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Marketplace\AdvertiserRepositoryInterface;
use NeneServe\Marketplace\CampaignRepositoryInterface;
use NeneServe\Marketplace\DealOpportunity;
use NeneServe\Marketplace\DealOpportunityRepositoryInterface;
use NeneServe\Marketplace\PdoDealOpportunityRepository;
use NeneServe\Marketplace\UseCase\CampaignNotFoundException;
use NeneServe\Marketplace\UseCase\DealHandoffFailedException;
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
    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private CampaignRepositoryInterface $campaigns,
        private AdvertiserRepositoryInterface $advertisers,
        private DealOpportunityRepositoryInterface $opportunities,
        private DatabaseTransactionManagerInterface $transactions,
        private DealClientInterface $deal,
        private RequestScopedHolder $organizationId,
    ) {
    }

    public function execute(HandoffCampaignToDealInput $input): HandoffCampaignToDealOutput
    {
        $organizationId = $this->organizationId->get();

        $campaign = $this->campaigns->findByIdInOrganization($input->campaignId, $organizationId);

        if ($campaign === null) {
            throw new CampaignNotFoundException();
        }

        $externalReference = sprintf('deal:%s:%s', $organizationId, $campaign->id);

        $existing = $this->opportunities->findByExternalReference($organizationId, $externalReference);

        if ($existing !== null && $existing->status === 'sent') {
            return new HandoffCampaignToDealOutput($existing); // idempotent: no duplicate opportunity
        }

        $advertiser = $this->advertisers->findByIdInOrganization($campaign->advertiserId, $organizationId);
        $advertiserName = $advertiser !== null ? $advertiser->name : $campaign->advertiserId;

        $pending = new DealOpportunity(
            'do-' . bin2hex(random_bytes(8)),
            $organizationId,
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
                static function (DatabaseQueryExecutorInterface $tx) use ($failed, $input, $organizationId): void {
                    (new PdoDealOpportunityRepository($tx))->save($failed);
                    (new PdoAuditLog($tx))->record(
                        $organizationId,
                        $input->actorUserId,
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

        $stored = $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($sent, $input, $organizationId): DealOpportunity {
                (new PdoDealOpportunityRepository($tx))->save($sent);
                (new PdoAuditLog($tx))->record(
                    $organizationId,
                    $input->actorUserId,
                    'deal.opportunity_sent',
                    'campaign',
                    $sent->campaignId,
                    ['external_reference' => $sent->externalReference, 'opportunity_id' => $sent->opportunityId, 'amount_cents' => $sent->amountCents],
                );

                return $sent;
            },
        );

        return new HandoffCampaignToDealOutput($stored);
    }
}
