<?php

declare(strict_types=1);

namespace NeneServe\Http\Auth;

use RuntimeException;

/**
 * Authentication failed: missing/invalid bearer token, or the principal could
 * no longer be resolved. Maps to Problem Details `unauthorized` (401).
 */
final class UnauthorizedException extends RuntimeException
{
}
