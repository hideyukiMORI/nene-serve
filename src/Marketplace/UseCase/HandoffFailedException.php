<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\UseCase;

use RuntimeException;

/**
 * The Invoice handoff transport failed after a successful reconciliation. Serving
 * is NOT paused (failure isolation); the handoff is recorded as `failed` and can
 * be retried idempotently. Maps to `invoice-handoff-failed` (502).
 */
final class HandoffFailedException extends RuntimeException
{
}
