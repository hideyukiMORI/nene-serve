<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\UseCase;

use RuntimeException;

/**
 * The snapshot did not reconcile (tampered hash, or amount ≠ units × rate). The
 * discrepancy is recorded and audited; the charge is NOT handed off (billing
 * §3.4 — discrepancies surfaced, never absorbed). Maps to `reconciliation-failed`
 * (409).
 */
final class ReconciliationFailedException extends RuntimeException
{
}
