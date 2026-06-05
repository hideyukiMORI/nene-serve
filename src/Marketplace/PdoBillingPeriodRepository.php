<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoBillingPeriodRepository implements BillingPeriodRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, campaign_id, period_start, period_end, status';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
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

    public function listByCampaign(string $organizationId, string $campaignId): array
    {
        $rows = $this->query->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM billing_periods WHERE organization_id = ? AND campaign_id = ? ORDER BY period_start',
            [$organizationId, $campaignId],
        );

        return array_map($this->hydrate(...), $rows);
    }

    public function save(BillingPeriod $period): void
    {
        $this->query->execute(
            'INSERT INTO billing_periods (id, organization_id, campaign_id, period_start, period_end, status)
             VALUES (?, ?, ?, ?, ?, ?) AS new
             ON DUPLICATE KEY UPDATE campaign_id = new.campaign_id, period_start = new.period_start, period_end = new.period_end, status = new.status',
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
