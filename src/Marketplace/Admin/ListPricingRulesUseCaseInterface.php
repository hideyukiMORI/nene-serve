<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

interface ListPricingRulesUseCaseInterface
{
    public function execute(ListPricingRulesInput $input): ListPricingRulesOutput;
}
