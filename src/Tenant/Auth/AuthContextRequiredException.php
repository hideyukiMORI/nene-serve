<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Auth;

use RuntimeException;

/**
 * No verified {@see \NeneServe\Tenant\AuthContext} could be rebuilt from the
 * request — the admin caller is unauthenticated. In practice unreachable behind
 * {@see AdminAuthMiddleware} (which already 401s), but {@see AuthContextResolver::require()}
 * raises it so handlers need no defensive null check. Maps to Problem Details
 * `unauthorized` (401).
 */
final class AuthContextRequiredException extends RuntimeException
{
}
