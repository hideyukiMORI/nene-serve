<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Auth;

use NeneServe\Tenant\AuthContext;
use NeneServe\Tenant\Role;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Rebuilds the {@see AuthContext} principal from the verified token claims that
 * {@see AdminAuthMiddleware} placed on the request. Admin handlers use this to
 * obtain the caller's user/tenant/role without re-parsing the Bearer token.
 */
final class AuthContextResolver
{
    /**
     * Same as {@see self::fromRequest()} but raises {@see AuthContextRequiredException}
     * (→ 401) instead of returning null, so admin handlers can resolve the
     * principal in one line without a defensive null check.
     */
    public static function require(ServerRequestInterface $request): AuthContext
    {
        return self::fromRequest($request)
            ?? throw new AuthContextRequiredException('Authentication is required.');
    }

    public static function fromRequest(ServerRequestInterface $request): ?AuthContext
    {
        $claims = $request->getAttribute(AdminAuthMiddleware::CLAIMS_ATTRIBUTE);

        if (!is_array($claims)) {
            return null;
        }

        $userId = is_string($claims['sub'] ?? null) ? $claims['sub'] : null;
        $organizationId = is_string($claims['org'] ?? null) ? $claims['org'] : null;
        $role = Role::tryFrom(is_string($claims['role'] ?? null) ? $claims['role'] : '');

        if ($userId === null || $organizationId === null || $role === null) {
            return null;
        }

        return new AuthContext($userId, $organizationId, $role);
    }
}
