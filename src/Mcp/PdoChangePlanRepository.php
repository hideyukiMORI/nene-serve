<?php

declare(strict_types=1);

namespace NeneServe\Mcp;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoChangePlanRepository implements ChangePlanRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, placement_id, new_creative_id, status, created_at';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function findByIdInOrganization(string $id, string $organizationId): ?ChangePlan
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::COLUMNS . ' FROM change_plans WHERE id = ? AND organization_id = ? LIMIT 1',
            [$id, $organizationId],
        );

        return $row === null ? null : $this->hydrate($row);
    }

    public function save(ChangePlan $plan): void
    {
        $this->query->execute(
            'INSERT INTO change_plans (id, organization_id, placement_id, new_creative_id, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?) AS new
             ON DUPLICATE KEY UPDATE placement_id = new.placement_id, new_creative_id = new.new_creative_id, status = new.status',
            [
                $plan->id,
                $plan->organizationId,
                $plan->placementId,
                $plan->newCreativeId,
                $plan->status,
                $plan->createdAt,
            ],
        );
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
