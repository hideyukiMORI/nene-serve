<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\UseCase;

use RuntimeException;

/**
 * Disallowed billing-period transition — e.g. closing an already-closed period
 * (closed figures are immutable, billing §3.2). Maps to
 * `invalid-period-transition` (409).
 */
final class InvalidPeriodTransitionException extends RuntimeException
{
}
