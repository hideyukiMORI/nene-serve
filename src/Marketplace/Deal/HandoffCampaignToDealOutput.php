<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Deal;

use NeneServe\Marketplace\DealOpportunity;

final readonly class HandoffCampaignToDealOutput
{
    public function __construct(
        public DealOpportunity $opportunity,
    ) {
    }
}
