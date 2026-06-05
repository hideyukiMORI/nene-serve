<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use NeneServe\Marketplace\UseCase\MarketplaceValidationException;

interface CreatePricingRuleUseCaseInterface
{
    /** @throws MarketplaceValidationException */
    public function execute(CreatePricingRuleInput $input): CreatePricingRuleOutput;
}
