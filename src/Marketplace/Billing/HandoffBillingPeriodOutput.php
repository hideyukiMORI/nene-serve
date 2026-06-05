<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

use NeneServe\Marketplace\InvoiceHandoff;

final readonly class HandoffBillingPeriodOutput
{
    public function __construct(
        public InvoiceHandoff $handoff,
    ) {
    }
}
