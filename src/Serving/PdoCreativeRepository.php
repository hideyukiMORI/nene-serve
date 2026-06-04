<?php

declare(strict_types=1);

namespace NeneServe\Serving;

use PDO;

final class PdoCreativeRepository implements CreativeRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, type, review_status, destination_url, asset_url, width, height, version, submitted_by, review_reason';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function findByIdInOrganization(string $id, string $organizationId): ?Creative
    {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::COLUMNS . ' FROM creatives WHERE id = ? AND organization_id = ? LIMIT 1',
        );
        $stmt->execute([$id, $organizationId]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    public function listByOrganization(string $organizationId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::COLUMNS . ' FROM creatives WHERE organization_id = ? ORDER BY id',
        );
        $stmt->execute([$organizationId]);

        return array_map($this->hydrate(...), array_values($stmt->fetchAll()));
    }

    public function save(Creative $creative): void
    {
        $stmt = $this->pdo->prepare(
            'REPLACE INTO creatives
                (id, organization_id, type, review_status, destination_url, asset_url, width, height, version, submitted_by, review_reason)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        );
        $stmt->execute([
            $creative->id,
            $creative->organizationId,
            $creative->type->value,
            $creative->reviewStatus->value,
            $creative->destinationUrl,
            $creative->assetUrl,
            $creative->width,
            $creative->height,
            $creative->version,
            $creative->submittedBy,
            $creative->reviewReason,
        ]);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): Creative
    {
        return new Creative(
            (string) $row['id'],
            (string) $row['organization_id'],
            CreativeType::from((string) $row['type']),
            ReviewStatus::from((string) $row['review_status']),
            (string) $row['destination_url'],
            $row['asset_url'] !== null ? (string) $row['asset_url'] : null,
            $row['width'] !== null ? (int) $row['width'] : null,
            $row['height'] !== null ? (int) $row['height'] : null,
            (int) $row['version'],
            $row['submitted_by'] !== null ? (string) $row['submitted_by'] : null,
            $row['review_reason'] !== null ? (string) $row['review_reason'] : null,
        );
    }
}
