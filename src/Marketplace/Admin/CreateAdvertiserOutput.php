<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use NeneServe\Marketplace\Advertiser;

final readonly class CreateAdvertiserOutput
{
    public function __construct(
        public Advertiser $advertiser,
    ) {
    }
}
