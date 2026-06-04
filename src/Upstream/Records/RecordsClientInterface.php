<?php

declare(strict_types=1);

namespace NeneServe\Upstream\Records;

/**
 * Read-only client for NeNe Records (sibling-products map, ADR 0002). Serve only
 * **reads** asset metadata (e.g. product image URLs) to assemble creatives; it
 * never writes to Records. HTTP only.
 */
interface RecordsClientInterface
{
    /**
     * @return RecordsAsset|null the asset, or null when not found
     *
     * @throws RecordsClientException on transport failure
     */
    public function fetchAsset(string $ref): ?RecordsAsset;
}
