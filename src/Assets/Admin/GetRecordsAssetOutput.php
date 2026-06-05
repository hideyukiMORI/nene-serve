<?php

declare(strict_types=1);

namespace NeneServe\Assets\Admin;

use NeneServe\Upstream\Records\RecordsAsset;

final readonly class GetRecordsAssetOutput
{
    public function __construct(
        public ?RecordsAsset $asset,
    ) {
    }
}
