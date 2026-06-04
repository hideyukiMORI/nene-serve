<?php

declare(strict_types=1);

namespace NeneServe\Upstream\Records;

/** In-memory Records client for boot/tests. Returns null for unknown refs. */
final class FakeRecordsClient implements RecordsClientInterface
{
    /** @var array<string, RecordsAsset> */
    private array $assets = [];

    /** @param list<RecordsAsset> $assets */
    public function __construct(array $assets = [])
    {
        foreach ($assets as $asset) {
            $this->assets[$asset->ref] = $asset;
        }
    }

    public function fetchAsset(string $ref): ?RecordsAsset
    {
        return $this->assets[$ref] ?? null;
    }
}
