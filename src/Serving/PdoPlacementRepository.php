<?php

declare(strict_types=1);

namespace NeneServe\Serving;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeneServe\Support\SqlDialect;

final readonly class PdoPlacementRepository implements PlacementRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, public_placement_key, allowed_origins, status, default_creative_id, measurement_enabled, frequency_cap, archived_at';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private SqlDialect $dialect = SqlDialect::Mysql,
    ) {
    }

    public function findByPublicKey(string $publicPlacementKey): ?Placement
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::COLUMNS . ' FROM placements WHERE public_placement_key = ? LIMIT 1',
            [$publicPlacementKey],
        );

        return $row === null ? null : $this->hydrateRow($row);
    }

    public function findByIdInOrganization(string $id, string $organizationId): ?Placement
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::COLUMNS . ' FROM placements WHERE id = ? AND organization_id = ? LIMIT 1',
            [$id, $organizationId],
        );

        return $row === null ? null : $this->hydrateRow($row);
    }

    public function listByOrganization(string $organizationId, int $limit, int $offset): array
    {
        $rows = $this->query->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM placements WHERE organization_id = ? ORDER BY public_placement_key LIMIT ? OFFSET ?',
            [$organizationId, $limit, $offset],
        );

        return array_map($this->hydrateRow(...), $rows);
    }

    public function save(Placement $placement): void
    {
        $this->query->execute(
            $this->dialect->upsert(
                'placements',
                ['id', 'organization_id', 'public_placement_key', 'allowed_origins', 'status', 'default_creative_id', 'measurement_enabled', 'frequency_cap', 'archived_at'],
                ['id'],
                ['public_placement_key', 'allowed_origins', 'status', 'default_creative_id', 'measurement_enabled', 'frequency_cap', 'archived_at'],
            ),
            [
                $placement->id,
                $placement->organizationId,
                $placement->publicPlacementKey,
                (string) json_encode($placement->allowedOrigins),
                $placement->status,
                $placement->defaultCreativeId,
                $placement->measurementEnabled ? 1 : 0,
                $placement->frequencyCap,
                $placement->archivedAt,
            ],
        );
    }

    public function archive(string $id, string $organizationId, string $at): void
    {
        $this->query->execute(
            "UPDATE placements SET status = 'archived', archived_at = ? WHERE id = ? AND organization_id = ?",
            [$at, $id, $organizationId],
        );
    }

    /** @param array<string, mixed> $row */
    private function hydrateRow(array $row): Placement
    {
        /** @var list<string> $origins */
        $origins = json_decode((string) $row['allowed_origins'], true) ?: [];

        return new Placement(
            (string) $row['id'],
            (string) $row['organization_id'],
            (string) $row['public_placement_key'],
            $origins,
            (string) $row['status'],
            $row['default_creative_id'] !== null ? (string) $row['default_creative_id'] : null,
            (bool) $row['measurement_enabled'],
            $row['frequency_cap'] !== null ? (int) $row['frequency_cap'] : null,
            $row['archived_at'] !== null ? (string) $row['archived_at'] : null,
        );
    }
}
