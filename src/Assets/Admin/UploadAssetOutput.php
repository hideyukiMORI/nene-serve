<?php

declare(strict_types=1);

namespace NeneServe\Assets\Admin;

use NeneServe\Assets\Asset;

final readonly class UploadAssetOutput
{
    public function __construct(
        public Asset $asset,
    ) {
    }
}
