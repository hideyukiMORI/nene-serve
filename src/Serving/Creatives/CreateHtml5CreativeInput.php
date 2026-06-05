<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

final readonly class CreateHtml5CreativeInput
{
    public function __construct(
        public string $actorUserId,
        public string $destinationUrl,
        public string $bundleId,
        public int $bundleSizeBytes,
        public int $assetCount,
        public string $htmlEntry,
        public ?int $width = null,
        public ?int $height = null,
        public ?string $campaignId = null,
    ) {
    }
}
