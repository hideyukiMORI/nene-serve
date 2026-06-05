<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

final readonly class ReviseCreativeInput
{
    public function __construct(
        public string $actorUserId,
        public string $creativeId,
        public string $destinationUrl,
        public string $assetUrl,
        public int $width,
        public int $height,
    ) {
    }
}
