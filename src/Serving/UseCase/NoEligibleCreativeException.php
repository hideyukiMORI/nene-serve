<?php

declare(strict_types=1);

namespace NeneServe\Serving\UseCase;

use RuntimeException;

/**
 * No approved creative is eligible to serve (inactive placement, no default,
 * not approved, or unsafe destination). This is an **empty serve**, not an
 * error: the handler returns 204 and nothing is counted (measurement-spec).
 */
final class NoEligibleCreativeException extends RuntimeException
{
}
