<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

final readonly class CreateImageCreativeInput
{
    public function __construct(
        public string $actorUserId,
        public string $destinationUrl,
        public string $assetUrl,
        public int $width,
        public int $height,
        public ?string $campaignId = null,
    ) {
    }
}
