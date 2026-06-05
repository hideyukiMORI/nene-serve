<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Marketplace\BillingPeriod;
use NeneServe\Marketplace\CampaignRepositoryInterface;
use NeneServe\Marketplace\PdoBillingPeriodRepository;
use NeneServe\Marketplace\UseCase\MarketplaceValidationException;

final readonly class OpenBillingPeriodUseCase implements OpenBillingPeriodUseCaseInterface
{
    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private CampaignRepositoryInterface $campaigns,
        private DatabaseTransactionManagerInterface $transactions,
        private RequestScopedHolder $organizationId,
    ) {
    }

    public function execute(OpenBillingPeriodInput $input): OpenBillingPeriodOutput
    {
        if (!self::isDate($input->periodStart) || !self::isDate($input->periodEnd) || $input->periodEnd < $input->periodStart) {
            throw new MarketplaceValidationException('period_start/period_end must be YYYY-MM-DD with end >= start.');
        }

        $organizationId = $this->organizationId->get();

        if ($this->campaigns->findByIdInOrganization($input->campaignId, $organizationId) === null) {
            throw new MarketplaceValidationException('Unknown campaign.');
        }

        $period = new BillingPeriod(
            'bp-' . bin2hex(random_bytes(8)),
            $organizationId,
            $input->campaignId,
            $input->periodStart,
            $input->periodEnd,
            'open',
        );

        $stored = $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($period, $input): BillingPeriod {
                (new PdoBillingPeriodRepository($tx))->save($period);
                (new PdoAuditLog($tx))->record(
                    $period->organizationId,
                    $input->actorUserId,
                    'billing_period.opened',
                    'billing_period',
                    $period->id,
                    ['after' => ['campaign_id' => $period->campaignId, 'period_start' => $period->periodStart, 'period_end' => $period->periodEnd]],
                );

                return $period;
            },
        );

        return new OpenBillingPeriodOutput($stored);
    }

    private static function isDate(string $value): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1;
    }
}
