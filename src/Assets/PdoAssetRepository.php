<?php

declare(strict_types=1);

namespace NeneServe\Assets;

use PDO;

final class PdoAssetRepository implements AssetRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, kind, content_type, byte_size';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function findById(string $id): ?Asset
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM assets WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    public function save(Asset $asset): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO assets (id, organization_id, kind, content_type, byte_size)
             VALUES (?, ?, ?, ?, ?)',
        );
        $stmt->execute([
            $asset->id,
            $asset->organizationId,
            $asset->kind,
            $asset->contentType,
            $asset->byteSize,
        ]);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): Asset
    {
        /** @var 'image'|'video' $kind */
        $kind = (string) $row['kind'];

        return new Asset(
            (string) $row['id'],
            (string) $row['organization_id'],
            $kind,
            (string) $row['content_type'],
            (int) $row['byte_size'],
        );
    }
}
