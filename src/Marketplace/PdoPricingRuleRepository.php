<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoPricingRuleRepository implements PricingRuleRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, name, pricing_model, rate_cents, version, created_at';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function findByIdInOrganization(string $id, string $organizationId): ?PricingRule
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::COLUMNS . ' FROM pricing_rules WHERE id = ? AND organization_id = ? LIMIT 1',
            [$id, $organizationId],
        );

        return $row === null ? null : $this->hydrate($row);
    }

    public function listByOrganization(string $organizationId): array
    {
        $rows = $this->query->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM pricing_rules WHERE organization_id = ? ORDER BY name, version',
            [$organizationId],
        );

        return array_map($this->hydrate(...), $rows);
    }

    public function currentVersion(string $organizationId, string $name): int
    {
        $row = $this->query->fetchOne(
            'SELECT COALESCE(MAX(version), 0) AS current FROM pricing_rules WHERE organization_id = ? AND name = ?',
            [$organizationId, $name],
        );

        return (int) ($row['current'] ?? 0);
    }

    public function save(PricingRule $rule): void
    {
        // INSERT only — pricing rules are immutable; a change is a new version row.
        $this->query->execute(
            'INSERT INTO pricing_rules (id, organization_id, name, pricing_model, rate_cents, version, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $rule->id,
                $rule->organizationId,
                $rule->name,
                $rule->model->value,
                $rule->rateCents,
                $rule->version,
                $rule->createdAt,
            ],
        );
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): PricingRule
    {
        return new PricingRule(
            (string) $row['id'],
            (string) $row['organization_id'],
            (string) $row['name'],
            PricingModel::from((string) $row['pricing_model']),
            (int) $row['rate_cents'],
            (int) $row['version'],
            (string) $row['created_at'],
        );
    }
}
