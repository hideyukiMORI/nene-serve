<?php

declare(strict_types=1);

namespace NeneServe\Http\RateLimit;

use RuntimeException;

/**
 * Raised when the rate limit storage cannot be configured or read.
 *
 * Thrown at container build time when production is asked for a store that
 * cannot enforce a limit, and at request time when the shared counter cannot be
 * read back. Both are deliberate hard failures: an exhausted rate limit denies
 * and never falls back to a more permissive path (api-security §28), so a
 * limiter that cannot function must not quietly serve traffic unthrottled.
 */
final class RateLimitStorageException extends RuntimeException
{
}
