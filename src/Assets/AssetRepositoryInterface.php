<?php

declare(strict_types=1);

namespace NeneServe\Assets;

interface AssetRepositoryInterface
{
    public function findById(string $id): ?Asset;

    public function save(Asset $asset): void;
}
