<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

use NeneServe\Marketplace\BillingPeriod;
use NeneServe\Marketplace\SpendSnapshot;
use NeneServe\Marketplace\UseCase\BillingPeriodNotFoundException;
use NeneServe\Marketplace\UseCase\InvalidPeriodTransitionException;
use NeneServe\Marketplace\UseCase\MarketplaceValidationException;
use NeneServe\Tenant\AuthContext;

interface CloseBillingPeriodUseCaseInterface
{
    /**
     * @return array{period: BillingPeriod, snapshot: SpendSnapshot}
     *
     * @throws BillingPeriodNotFoundException
     * @throws InvalidPeriodTransitionException
     * @throws MarketplaceValidationException
     */
    public function execute(AuthContext $actor, string $periodId): array;
}
