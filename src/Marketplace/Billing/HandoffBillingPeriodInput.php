<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

final readonly class HandoffBillingPeriodInput
{
    public function __construct(
        public string $actorUserId,
        public string $periodId,
    ) {
    }
}
