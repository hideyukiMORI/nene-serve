<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

use NeneServe\Marketplace\UseCase\MarketplaceValidationException;

interface OpenBillingPeriodUseCaseInterface
{
    /** @throws MarketplaceValidationException */
    public function execute(OpenBillingPeriodInput $input): OpenBillingPeriodOutput;
}
