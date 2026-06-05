<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use NeneServe\Marketplace\PricingRule;

final readonly class CreatePricingRuleOutput
{
    public function __construct(
        public PricingRule $pricingRule,
    ) {
    }
}
