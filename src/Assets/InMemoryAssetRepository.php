<?php

declare(strict_types=1);

namespace NeneServe\Assets;

final class InMemoryAssetRepository implements AssetRepositoryInterface
{
    /** @var array<string, Asset> */
    private array $byId = [];

    public function findById(string $id): ?Asset
    {
        return $this->byId[$id] ?? null;
    }

    public function save(Asset $asset): void
    {
        $this->byId[$asset->id] = $asset;
    }
}
