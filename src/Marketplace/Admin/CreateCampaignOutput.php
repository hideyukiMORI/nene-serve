<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use NeneServe\Marketplace\Campaign;

final readonly class CreateCampaignOutput
{
    public function __construct(
        public Campaign $campaign,
    ) {
    }
}
