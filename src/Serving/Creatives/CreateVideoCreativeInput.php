<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

final readonly class CreateVideoCreativeInput
{
    public function __construct(
        public string $actorUserId,
        public string $destinationUrl,
        public string $assetUrl,
        public string $posterUrl,
        public int $width,
        public int $height,
        public int $durationSeconds,
        public ?string $campaignId = null,
    ) {
    }
}
