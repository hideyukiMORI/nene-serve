<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

final readonly class GetBillingPeriodInput
{
    public function __construct(
        public string $id,
    ) {
    }
}
