<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Marketplace\BillingPeriod;
use NeneServe\Marketplace\PdoBillingPeriodRepository;
use NeneServe\Marketplace\PdoCampaignRepository;
use NeneServe\Marketplace\UseCase\MarketplaceValidationException;
use NeneServe\Tenant\AuthContext;

final readonly class OpenBillingPeriodUseCase implements OpenBillingPeriodUseCaseInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private DatabaseTransactionManagerInterface $transactions,
    ) {
    }

    public function execute(AuthContext $actor, string $campaignId, string $periodStart, string $periodEnd): BillingPeriod
    {
        if (!self::isDate($periodStart) || !self::isDate($periodEnd) || $periodEnd < $periodStart) {
            throw new MarketplaceValidationException('period_start/period_end must be YYYY-MM-DD with end >= start.');
        }

        if ((new PdoCampaignRepository($this->query))->findByIdInOrganization($campaignId, $actor->organizationId) === null) {
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

        return $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($period, $actor): BillingPeriod {
                (new PdoBillingPeriodRepository($tx))->save($period);
                (new PdoAuditLog($tx))->record(
                    $actor->organizationId,
                    $actor->userId,
                    'billing_period.opened',
                    'billing_period',
                    $period->id,
                    ['after' => ['campaign_id' => $period->campaignId, 'period_start' => $period->periodStart, 'period_end' => $period->periodEnd]],
                );

                return $period;
            },
        );
    }

    private static function isDate(string $value): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1;
    }
}
