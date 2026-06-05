<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

final readonly class OpenBillingPeriodInput
{
    public function __construct(
        public string $actorUserId,
        public string $campaignId,
        public string $periodStart,
        public string $periodEnd,
    ) {
    }
}
