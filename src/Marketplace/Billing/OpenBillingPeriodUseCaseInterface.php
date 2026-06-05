<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

use NeneServe\Marketplace\BillingPeriod;
use NeneServe\Marketplace\UseCase\MarketplaceValidationException;
use NeneServe\Tenant\AuthContext;

interface OpenBillingPeriodUseCaseInterface
{
    /** @throws MarketplaceValidationException */
    public function execute(AuthContext $actor, string $campaignId, string $periodStart, string $periodEnd): BillingPeriod;
}
