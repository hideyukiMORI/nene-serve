<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

interface HandoffBillingPeriodUseCaseInterface
{
    public function execute(HandoffBillingPeriodInput $input): HandoffBillingPeriodOutput;
}
