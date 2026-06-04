<?php

declare(strict_types=1);

namespace NeneServe\Mcp;

use PDO;

final class PdoChangePlanRepository implements ChangePlanRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, placement_id, new_creative_id, status, created_at';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function findByIdInOrganization(string $id, string $organizationId): ?ChangePlan
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM change_plans WHERE id = ? AND organization_id = ? LIMIT 1');
        $stmt->execute([$id, $organizationId]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    public function save(ChangePlan $plan): void
    {
        $stmt = $this->pdo->prepare(
            'REPLACE INTO change_plans (id, organization_id, placement_id, new_creative_id, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?)',
        );
        $stmt->execute([
            $plan->id,
            $plan->organizationId,
            $plan->placementId,
            $plan->newCreativeId,
            $plan->status,
            $plan->createdAt,
        ]);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): ChangePlan
    {
        return new ChangePlan(
            (string) $row['id'],
            (string) $row['organization_id'],
            (string) $row['placement_id'],
            (string) $row['new_creative_id'],
            (string) $row['status'],
            (string) $row['created_at'],
        );
    }
}
