<?php

declare(strict_types=1);

namespace NeneServe\Serving;

use PDO;

final class PdoCreativeRepository implements CreativeRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, type, review_status, destination_url, asset_url, width, height, version';

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
        if ($row === false) {
            return null;
        }

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
        );
    }
}
