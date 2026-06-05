<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

use NeneServe\Marketplace\UseCase\BillingPeriodNotFoundException;
use NeneServe\Marketplace\UseCase\InvalidPeriodTransitionException;
use NeneServe\Marketplace\UseCase\MarketplaceValidationException;

interface CloseBillingPeriodUseCaseInterface
{
    /**
     * @throws BillingPeriodNotFoundException
     * @throws InvalidPeriodTransitionException
     * @throws MarketplaceValidationException
     */
    public function execute(CloseBillingPeriodInput $input): CloseBillingPeriodOutput;
}
