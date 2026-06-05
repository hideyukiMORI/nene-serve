<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

use NeneServe\Marketplace\BillingPeriod;
use NeneServe\Marketplace\SpendSnapshot;

final readonly class GetBillingPeriodOutput
{
    public function __construct(
        public BillingPeriod $period,
        public ?SpendSnapshot $latestSnapshot,
    ) {
    }
}
