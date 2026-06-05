<?php

declare(strict_types=1);

namespace NeneServe\Assets;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoAssetRepository implements AssetRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, kind, content_type, byte_size';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function findById(string $id): ?Asset
    {
        $row = $this->query->fetchOne('SELECT ' . self::COLUMNS . ' FROM assets WHERE id = ? LIMIT 1', [$id]);

        return $row === null ? null : $this->hydrate($row);
    }

    public function save(Asset $asset): void
    {
        $this->query->execute(
            'INSERT INTO assets (id, organization_id, kind, content_type, byte_size)
             VALUES (?, ?, ?, ?, ?)',
            [
                $asset->id,
                $asset->organizationId,
                $asset->kind,
                $asset->contentType,
                $asset->byteSize,
            ],
        );
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
