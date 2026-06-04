<?php

declare(strict_types=1);

namespace NeneServe\Serving;

/**
 * A reviewable creative. Only an `approved` creative is ever served (ADR 0020);
 * `destinationUrl` is registered here and is the only redirect target the click
 * endpoint will use (no open redirect — ADR 0019/0021).
 */
final class Creative
{
    public function __construct(
        public readonly string $id,
        public readonly string $organizationId,
        public readonly CreativeType $type,
        public readonly ReviewStatus $reviewStatus,
        public readonly string $destinationUrl,
        public readonly ?string $assetUrl = null,
        public readonly ?int $width = null,
        public readonly ?int $height = null,
        public readonly int $version = 1,
    ) {
    }

    public function isServable(): bool
    {
        return $this->reviewStatus->isServable();
    }

    /**
     * Public render payload — no internal ids, org data, or review metadata
     * (least exposure, api-security §2).
     *
     * @return array<string, mixed>
     */
    public function toServePayload(): array
    {
        return array_filter([
            'type' => $this->type->value,
            'asset_url' => $this->assetUrl,
            'width' => $this->width,
            'height' => $this->height,
        ], static fn ($v) => $v !== null);
    }
}
