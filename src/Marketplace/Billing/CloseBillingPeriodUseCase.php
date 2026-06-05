<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Marketplace\PdoBillingPeriodRepository;
use NeneServe\Marketplace\PdoCampaignRepository;
use NeneServe\Marketplace\PdoSpendSnapshotRepository;
use NeneServe\Marketplace\SpendSnapshot;
use NeneServe\Marketplace\SpendSnapshotHasher;
use NeneServe\Marketplace\UseCase\BillingPeriodNotFoundException;
use NeneServe\Marketplace\UseCase\GetCampaignSpendUseCase;
use NeneServe\Marketplace\UseCase\InvalidPeriodTransitionException;
use NeneServe\Marketplace\UseCase\MarketplaceValidationException;
use NeneServe\Tenant\AuthContext;

/**
 * Closes an open billing period: derives the reproducible spend, persists an
 * immutable, hash-chained spend snapshot, and marks the period closed — all in
 * one transaction (billing-and-accounting §3, ADR 0015). Closed figures are
 * immutable.
 */
final readonly class CloseBillingPeriodUseCase implements CloseBillingPeriodUseCaseInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private DatabaseTransactionManagerInterface $transactions,
        private GetCampaignSpendUseCase $campaignSpend,
    ) {
    }

    public function execute(AuthContext $actor, string $periodId): array
    {
        $period = (new PdoBillingPeriodRepository($this->query))->findByIdInOrganization($periodId, $actor->organizationId);

        if ($period === null) {
            throw new BillingPeriodNotFoundException();
        }

        if (!$period->isOpen()) {
            throw new InvalidPeriodTransitionException('Only an open period can be closed; closed figures are immutable.');
        }

        $campaign = (new PdoCampaignRepository($this->query))->findByIdInOrganization($period->campaignId, $actor->organizationId);

        if ($campaign === null) {
            throw new MarketplaceValidationException('Campaign for this period no longer exists.');
        }

        $spend = $this->campaignSpend->forCampaign($campaign);
        $version = (new PdoSpendSnapshotRepository($this->query))->currentVersion($actor->organizationId, $period->id) + 1;
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

        return $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($snapshot, $closed, $actor, $spend): array {
                (new PdoSpendSnapshotRepository($tx))->save($snapshot);
                (new PdoBillingPeriodRepository($tx))->save($closed);
                (new PdoAuditLog($tx))->record(
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
            },
        );
    }
}
