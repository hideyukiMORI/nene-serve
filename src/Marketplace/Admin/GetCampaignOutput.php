<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use NeneServe\Marketplace\Campaign;
use NeneServe\Marketplace\CampaignSpend;

final readonly class GetCampaignOutput
{
    public function __construct(
        public Campaign $campaign,
        public CampaignSpend $spend,
    ) {
    }
}
