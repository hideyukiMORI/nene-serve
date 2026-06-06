<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeneServe\Support\SqlDialect;

final readonly class PdoBillingPeriodRepository implements BillingPeriodRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, campaign_id, period_start, period_end, status';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private SqlDialect $dialect = SqlDialect::Mysql,
    ) {
    }

    public function findByIdInOrganization(string $id, string $organizationId): ?BillingPeriod
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::COLUMNS . ' FROM billing_periods WHERE id = ? AND organization_id = ? LIMIT 1',
            [$id, $organizationId],
        );

        return $row === null ? null : $this->hydrate($row);
    }

    public function save(BillingPeriod $period): void
    {
        $this->query->execute(
            $this->dialect->upsert(
                'billing_periods',
                ['id', 'organization_id', 'campaign_id', 'period_start', 'period_end', 'status'],
                ['id'],
                ['campaign_id', 'period_start', 'period_end', 'status'],
            ),
            [
                $period->id,
                $period->organizationId,
                $period->campaignId,
                $period->periodStart,
                $period->periodEnd,
                $period->status,
            ],
        );
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): BillingPeriod
    {
        return new BillingPeriod(
            (string) $row['id'],
            (string) $row['organization_id'],
            (string) $row['campaign_id'],
            (string) $row['period_start'],
            (string) $row['period_end'],
            (string) $row['status'],
        );
    }
}
