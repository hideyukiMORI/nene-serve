<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

final readonly class GetCampaignInput
{
    public function __construct(
        public string $id,
    ) {
    }
}
