<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

use NeneServe\Marketplace\InvoiceHandoff;
use NeneServe\Tenant\AuthContext;

interface HandoffBillingPeriodUseCaseInterface
{
    public function execute(AuthContext $actor, string $periodId): InvoiceHandoff;
}
