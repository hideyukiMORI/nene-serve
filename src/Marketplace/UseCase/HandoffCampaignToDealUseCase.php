<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\UseCase;

use NeneServe\Audit\AuditLogInterface;
use NeneServe\Marketplace\AdvertiserRepositoryInterface;
use NeneServe\Marketplace\CampaignRepositoryInterface;
use NeneServe\Marketplace\DealOpportunity;
use NeneServe\Marketplace\DealOpportunityRepositoryInterface;
use NeneServe\Support\TransactionManagerInterface;
use NeneServe\Tenant\AuthContext;
use NeneServe\Upstream\Deal\DealClientException;
use NeneServe\Upstream\Deal\DealClientInterface;

/**
 * Hands a campaign to NeNe Deal as a sales opportunity (sibling map Phase 4+,
 * ADR 0002). **Idempotent on external_reference** (no duplicate opportunity);
 * **audited**; transport failure is **isolated** (serving unaffected) and
 * retryable. Net amount only (no tax).
 */
final class HandoffCampaignToDealUseCase
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaigns,
        private readonly AdvertiserRepositoryInterface $advertisers,
        private readonly DealOpportunityRepositoryInterface $opportunities,
        private readonly DealClientInterface $deal,
        private readonly AuditLogInterface $audit,
        private readonly TransactionManagerInterface $tx,
    ) {
    }

    public function execute(AuthContext $actor, string $campaignId): DealOpportunity
    {
        $campaign = $this->campaigns->findByIdInOrganization($campaignId, $actor->organizationId);
        if ($campaign === null) {
            throw new CampaignNotFoundException();
        }

        $externalReference = sprintf('deal:%s:%s', $actor->organizationId, $campaign->id);

        $existing = $this->opportunities->findByExternalReference($actor->organizationId, $externalReference);
        if ($existing !== null && $existing->status === 'sent') {
            return $existing; // idempotent: no duplicate opportunity
        }

        $advertiser = $this->advertisers->findByIdInOrganization($campaign->advertiserId, $actor->organizationId);
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
            $this->tx->transactional(function () use ($failed, $actor): void {
                $this->opportunities->save($failed);
                $this->audit->record(
                    $actor->organizationId,
                    $actor->userId,
                    'deal.opportunity_failed',
                    'campaign',
                    $failed->campaignId,
                    ['external_reference' => $failed->externalReference],
                );
            });

            throw new DealHandoffFailedException('Deal handoff failed: ' . $e->getMessage(), 0, $e);
        }

        $sent = $pending->withResult('sent', $result->opportunityId);

        return $this->tx->transactional(function () use ($sent, $actor): DealOpportunity {
            $this->opportunities->save($sent);
            $this->audit->record(
                $actor->organizationId,
                $actor->userId,
                'deal.opportunity_sent',
                'campaign',
                $sent->campaignId,
                ['external_reference' => $sent->externalReference, 'opportunity_id' => $sent->opportunityId, 'amount_cents' => $sent->amountCents],
            );

            return $sent;
        });
    }
}
