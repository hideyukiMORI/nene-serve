<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

final readonly class CreateCampaignInput
{
    public function __construct(
        public string $actorUserId,
        public string $advertiserId,
        public string $name,
        public string $pricingRuleId,
        public int $budgetCents,
        public bool $pauseOnBudgetExhausted = true,
        public string $status = 'draft',
        public string $fundingStatus = 'unfunded',
    ) {
    }
}
