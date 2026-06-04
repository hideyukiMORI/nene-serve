<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\UseCase;

use NeneServe\Audit\AuditLogInterface;
use NeneServe\Marketplace\BillingPeriod;
use NeneServe\Marketplace\BillingPeriodRepositoryInterface;
use NeneServe\Marketplace\CampaignRepositoryInterface;
use NeneServe\Support\TransactionManagerInterface;
use NeneServe\Tenant\AuthContext;

final class OpenBillingPeriodUseCase
{
    public function __construct(
        private readonly BillingPeriodRepositoryInterface $periods,
        private readonly CampaignRepositoryInterface $campaigns,
        private readonly AuditLogInterface $audit,
        private readonly TransactionManagerInterface $tx,
    ) {
    }

    public function execute(AuthContext $actor, string $campaignId, string $periodStart, string $periodEnd): BillingPeriod
    {
        if (!self::isDate($periodStart) || !self::isDate($periodEnd) || $periodEnd < $periodStart) {
            throw new MarketplaceValidationException('period_start/period_end must be YYYY-MM-DD with end >= start.');
        }
        if ($this->campaigns->findByIdInOrganization($campaignId, $actor->organizationId) === null) {
            throw new MarketplaceValidationException('Unknown campaign.');
        }

        $period = new BillingPeriod(
            'bp-' . bin2hex(random_bytes(8)),
            $actor->organizationId,
            $campaignId,
            $periodStart,
            $periodEnd,
            'open',
        );

        return $this->tx->transactional(function () use ($period, $actor): BillingPeriod {
            $this->periods->save($period);
            $this->audit->record(
                $actor->organizationId,
                $actor->userId,
                'billing_period.opened',
                'billing_period',
                $period->id,
                ['after' => ['campaign_id' => $period->campaignId, 'period_start' => $period->periodStart, 'period_end' => $period->periodEnd]],
            );

            return $period;
        });
    }

    private static function isDate(string $value): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1;
    }
}
