<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

use PDO;

final class PdoBillingPeriodRepository implements BillingPeriodRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, campaign_id, period_start, period_end, status';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function findByIdInOrganization(string $id, string $organizationId): ?BillingPeriod
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM billing_periods WHERE id = ? AND organization_id = ? LIMIT 1');
        $stmt->execute([$id, $organizationId]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    public function listByCampaign(string $organizationId, string $campaignId): array
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM billing_periods WHERE organization_id = ? AND campaign_id = ? ORDER BY period_start');
        $stmt->execute([$organizationId, $campaignId]);

        return array_map($this->hydrate(...), array_values($stmt->fetchAll()));
    }

    public function save(BillingPeriod $period): void
    {
        $stmt = $this->pdo->prepare(
            'REPLACE INTO billing_periods (id, organization_id, campaign_id, period_start, period_end, status)
             VALUES (?, ?, ?, ?, ?, ?)',
        );
        $stmt->execute([
            $period->id,
            $period->organizationId,
            $period->campaignId,
            $period->periodStart,
            $period->periodEnd,
            $period->status,
        ]);
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
