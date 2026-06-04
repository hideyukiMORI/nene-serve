<?php

declare(strict_types=1);

namespace NeneServe\Mcp\UseCase;

use RuntimeException;

/** A plan that is not `proposed` cannot be applied (idempotency). Maps to `invalid-plan-state` (409). */
final class InvalidPlanStateException extends RuntimeException
{
}
