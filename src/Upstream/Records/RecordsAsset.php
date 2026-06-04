<?php

declare(strict_types=1);

namespace NeneServe\Upstream\Records;

/**
 * Read-only asset metadata fetched from NeNe Records for creative assembly
 * (sibling-products map, ADR 0002). Serve never writes to Records.
 */
final class RecordsAsset
{
    public function __construct(
        public readonly string $ref,
        public readonly string $imageUrl,
        public readonly ?int $width = null,
        public readonly ?int $height = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'ref' => $this->ref,
            'image_url' => $this->imageUrl,
            'width' => $this->width,
            'height' => $this->height,
        ], static fn ($v) => $v !== null);
    }
}
