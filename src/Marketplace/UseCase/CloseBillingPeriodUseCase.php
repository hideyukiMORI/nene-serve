<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\UseCase;

use NeneServe\Audit\AuditLogInterface;
use NeneServe\Marketplace\BillingPeriod;
use NeneServe\Marketplace\BillingPeriodRepositoryInterface;
use NeneServe\Marketplace\CampaignRepositoryInterface;
use NeneServe\Marketplace\SpendSnapshot;
use NeneServe\Marketplace\SpendSnapshotHasher;
use NeneServe\Marketplace\SpendSnapshotRepositoryInterface;
use NeneServe\Support\TransactionManagerInterface;
use NeneServe\Tenant\AuthContext;

/**
 * Closes a billing period (billing §3.2): derives spend once, writes a versioned
 * tamper-evident SpendSnapshot, and marks the period `closed` — atomically and
 * audited. A closed period cannot be re-closed; its figures are immutable
 * (corrections are additive adjustments in a later period, never edits).
 *
 * @phpstan-type Closed array{period: BillingPeriod, snapshot: SpendSnapshot}
 */
final class CloseBillingPeriodUseCase
{
    public function __construct(
        private readonly BillingPeriodRepositoryInterface $periods,
        private readonly CampaignRepositoryInterface $campaigns,
        private readonly SpendSnapshotRepositoryInterface $snapshots,
        private readonly GetCampaignSpendUseCase $campaignSpend,
        private readonly AuditLogInterface $audit,
        private readonly TransactionManagerInterface $tx,
    ) {
    }

    /**
     * @return array{period: BillingPeriod, snapshot: SpendSnapshot}
     */
    public function execute(AuthContext $actor, string $periodId): array
    {
        $period = $this->periods->findByIdInOrganization($periodId, $actor->organizationId);
        if ($period === null) {
            throw new BillingPeriodNotFoundException();
        }
        if (!$period->isOpen()) {
            throw new InvalidPeriodTransitionException('Only an open period can be closed; closed figures are immutable.');
        }

        $campaign = $this->campaigns->findByIdInOrganization($period->campaignId, $actor->organizationId);
        if ($campaign === null) {
            throw new MarketplaceValidationException('Campaign for this period no longer exists.');
        }

        $spend = $this->campaignSpend->forCampaign($campaign);
        $version = $this->snapshots->currentVersion($actor->organizationId, $period->id) + 1;
        $hash = SpendSnapshotHasher::compute(
            $actor->organizationId,
            $period->id,
            $version,
            $spend->impressions,
            $spend->clicks,
            $campaign->pricingRuleId,
            $spend->pricingRuleVersion,
            $spend->spentCents,
        );
        $snapshot = new SpendSnapshot(
            'ss-' . bin2hex(random_bytes(8)),
            $actor->organizationId,
            $period->id,
            $version,
            $spend->impressions,
            $spend->clicks,
            $campaign->pricingRuleId,
            $spend->pricingRuleVersion,
            $spend->spentCents,
            $hash,
            gmdate('c'),
        );
        $closed = $period->withStatus('closed');

        return $this->tx->transactional(function () use ($snapshot, $closed, $actor, $spend): array {
            $this->snapshots->save($snapshot);
            $this->periods->save($closed);
            $this->audit->record(
                $actor->organizationId,
                $actor->userId,
                'billing_period.closed',
                'billing_period',
                $closed->id,
                [
                    'before' => ['status' => 'open'],
                    'after' => ['status' => 'closed'],
                    'spend_snapshot_version' => $snapshot->version,
                    'spent_cents' => $spend->spentCents,
                    'billable_impressions' => $spend->impressions,
                    'billable_clicks' => $spend->clicks,
                ],
            );

            return ['period' => $closed, 'snapshot' => $snapshot];
        });
    }
}
