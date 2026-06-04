<?php

declare(strict_types=1);

namespace NeneServe\Assets;

final class Asset
{
    /** @param 'image'|'video' $kind */
    public function __construct(
        public readonly string $id,
        public readonly string $organizationId,
        public readonly string $kind,
        public readonly string $contentType,
        public readonly int $byteSize,
    ) {
    }

    /** @return array{id: string, kind: string, content_type: string, byte_size: int, asset_url: string} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->kind,
            'content_type' => $this->contentType,
            'byte_size' => $this->byteSize,
            'asset_url' => '/public/assets/' . $this->id,
        ];
    }
}
