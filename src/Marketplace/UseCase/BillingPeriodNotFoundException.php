<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\UseCase;

use RuntimeException;

/** Billing period not found in the tenant. Maps to `billing-period-not-found` (404). */
final class BillingPeriodNotFoundException extends RuntimeException
{
}
