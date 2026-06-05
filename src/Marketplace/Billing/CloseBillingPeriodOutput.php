<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

use NeneServe\Marketplace\BillingPeriod;
use NeneServe\Marketplace\SpendSnapshot;

final readonly class CloseBillingPeriodOutput
{
    public function __construct(
        public BillingPeriod $period,
        public SpendSnapshot $snapshot,
    ) {
    }
}
