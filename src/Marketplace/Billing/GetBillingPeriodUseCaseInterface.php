<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

use NeneServe\Marketplace\UseCase\BillingPeriodNotFoundException;

interface GetBillingPeriodUseCaseInterface
{
    /**
     * @throws BillingPeriodNotFoundException
     */
    public function execute(GetBillingPeriodInput $input): GetBillingPeriodOutput;
}
