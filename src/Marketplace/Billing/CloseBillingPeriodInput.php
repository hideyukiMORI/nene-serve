<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

final readonly class CloseBillingPeriodInput
{
    public function __construct(
        public string $actorUserId,
        public string $periodId,
    ) {
    }
}
