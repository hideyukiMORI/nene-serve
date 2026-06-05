<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use NeneServe\Marketplace\Campaign;
use NeneServe\Marketplace\UseCase\MarketplaceValidationException;
use NeneServe\Tenant\AuthContext;

interface CreateCampaignUseCaseInterface
{
    /** @throws MarketplaceValidationException */
    public function execute(
        AuthContext $actor,
        string $advertiserId,
        string $name,
        string $pricingRuleId,
        int $budgetCents,
        bool $pauseOnBudgetExhausted = true,
        string $status = 'draft',
        string $fundingStatus = 'unfunded',
    ): Campaign;
}
