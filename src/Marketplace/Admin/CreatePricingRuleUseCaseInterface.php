<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use NeneServe\Marketplace\PricingRule;
use NeneServe\Marketplace\UseCase\MarketplaceValidationException;
use NeneServe\Tenant\AuthContext;

interface CreatePricingRuleUseCaseInterface
{
    /** @throws MarketplaceValidationException */
    public function execute(AuthContext $actor, string $name, string $model, int $rateCents): PricingRule;
}
