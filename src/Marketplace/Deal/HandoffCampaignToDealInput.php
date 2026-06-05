<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Deal;

final readonly class HandoffCampaignToDealInput
{
    public function __construct(
        public string $actorUserId,
        public string $campaignId,
    ) {
    }
}
