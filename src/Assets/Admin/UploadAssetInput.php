<?php

declare(strict_types=1);

namespace NeneServe\Assets\Admin;

final readonly class UploadAssetInput
{
    public function __construct(
        public string $actorUserId,
        public string $contentType,
        public string $bytes,
    ) {
    }
}
