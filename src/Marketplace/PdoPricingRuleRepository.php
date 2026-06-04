<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

use PDO;

final class PdoPricingRuleRepository implements PricingRuleRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, name, pricing_model, rate_cents, version, created_at';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function findByIdInOrganization(string $id, string $organizationId): ?PricingRule
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM pricing_rules WHERE id = ? AND organization_id = ? LIMIT 1');
        $stmt->execute([$id, $organizationId]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    public function listByOrganization(string $organizationId): array
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM pricing_rules WHERE organization_id = ? ORDER BY name, version');
        $stmt->execute([$organizationId]);

        return array_map($this->hydrate(...), array_values($stmt->fetchAll()));
    }

    public function currentVersion(string $organizationId, string $name): int
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(MAX(version), 0) FROM pricing_rules WHERE organization_id = ? AND name = ?');
        $stmt->execute([$organizationId, $name]);

        return (int) $stmt->fetchColumn();
    }

    public function save(PricingRule $rule): void
    {
        // INSERT only — pricing rules are immutable; a change is a new version row.
        $stmt = $this->pdo->prepare(
            'INSERT INTO pricing_rules (id, organization_id, name, pricing_model, rate_cents, version, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
        );
        $stmt->execute([
            $rule->id,
            $rule->organizationId,
            $rule->name,
            $rule->model->value,
            $rule->rateCents,
            $rule->version,
            $rule->createdAt,
        ]);
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
