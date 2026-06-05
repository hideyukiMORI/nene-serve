<?php

declare(strict_types=1);

namespace NeneServe\Assets\Admin;

final readonly class GetRecordsAssetInput
{
    public function __construct(
        public string $ref,
    ) {
    }
}
