<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

use NeneServe\Marketplace\BillingPeriod;

final readonly class OpenBillingPeriodOutput
{
    public function __construct(
        public BillingPeriod $period,
    ) {
    }
}
