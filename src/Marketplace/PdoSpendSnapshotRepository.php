<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoSpendSnapshotRepository implements SpendSnapshotRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, billing_period_id, version, billable_impressions, billable_clicks, pricing_rule_id, pricing_rule_version, spent_cents, hash, created_at';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function currentVersion(string $organizationId, string $billingPeriodId): int
    {
        $row = $this->query->fetchOne(
            'SELECT COALESCE(MAX(version), 0) AS current FROM spend_snapshots WHERE organization_id = ? AND billing_period_id = ?',
            [$organizationId, $billingPeriodId],
        );

        return (int) ($row['current'] ?? 0);
    }

    public function latestForPeriod(string $organizationId, string $billingPeriodId): ?SpendSnapshot
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::COLUMNS . ' FROM spend_snapshots WHERE organization_id = ? AND billing_period_id = ? ORDER BY version DESC LIMIT 1',
            [$organizationId, $billingPeriodId],
        );

        return $row === null ? null : $this->hydrate($row);
    }

    public function save(SpendSnapshot $snapshot): void
    {
        // INSERT only — snapshots are append-only/immutable (re-derive = new version).
        $this->query->execute(
            'INSERT INTO spend_snapshots
                (id, organization_id, billing_period_id, version, billable_impressions, billable_clicks, pricing_rule_id, pricing_rule_version, spent_cents, hash, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $snapshot->id,
                $snapshot->organizationId,
                $snapshot->billingPeriodId,
                $snapshot->version,
                $snapshot->billableImpressions,
                $snapshot->billableClicks,
                $snapshot->pricingRuleId,
                $snapshot->pricingRuleVersion,
                $snapshot->spentCents,
                $snapshot->hash,
                $snapshot->createdAt,
            ],
        );
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): SpendSnapshot
    {
        return new SpendSnapshot(
            (string) $row['id'],
            (string) $row['organization_id'],
            (string) $row['billing_period_id'],
            (int) $row['version'],
            (int) $row['billable_impressions'],
            (int) $row['billable_clicks'],
            (string) $row['pricing_rule_id'],
            (int) $row['pricing_rule_version'],
            (int) $row['spent_cents'],
            (string) $row['hash'],
            (string) $row['created_at'],
        );
    }
}
