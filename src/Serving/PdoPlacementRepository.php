<?php

declare(strict_types=1);

namespace NeneServe\Serving;

use PDO;

final class PdoPlacementRepository implements PlacementRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, public_placement_key, allowed_origins, status, default_creative_id';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function findByPublicKey(string $publicPlacementKey): ?Placement
    {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::COLUMNS . ' FROM placements WHERE public_placement_key = ? LIMIT 1',
        );
        $stmt->execute([$publicPlacementKey]);

        return $this->hydrate($stmt->fetch());
    }

    public function findByIdInOrganization(string $id, string $organizationId): ?Placement
    {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::COLUMNS . ' FROM placements WHERE id = ? AND organization_id = ? LIMIT 1',
        );
        $stmt->execute([$id, $organizationId]);

        return $this->hydrate($stmt->fetch());
    }

    public function listByOrganization(string $organizationId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::COLUMNS . ' FROM placements WHERE organization_id = ? ORDER BY public_placement_key',
        );
        $stmt->execute([$organizationId]);

        return array_map($this->hydrateRow(...), array_values($stmt->fetchAll()));
    }

    /** @param array<string, mixed>|false $row */
    private function hydrate(array|false $row): ?Placement
    {
        return $row === false ? null : $this->hydrateRow($row);
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
        );
    }
}
