<?php

declare(strict_types=1);

namespace NeneServe\Http\Auth;

use NeneServe\Http\Request;
use NeneServe\Tenant\AuthContext;
use NeneServe\Tenant\Role;
use NeneServe\Tenant\UserRepositoryInterface;
use ValueError;

/**
 * Resolves an {@see AuthContext} from a `Authorization: Bearer <jwt>` header.
 *
 * Fail closed (api-security §0.3): any missing/invalid token, unknown user, or
 * disabled account throws {@see UnauthorizedException}. The user is re-loaded
 * from storage and the **stored** role/status — not the token claim — is
 * authoritative, so a revoked or downgraded account cannot ride an old token.
 * The lookup is tenant-scoped by the token's `org` claim (ADR 0006).
 */
final class BearerTokenMiddleware
{
    public function __construct(
        private readonly Jwt $jwt,
        private readonly UserRepositoryInterface $users,
    ) {
    }

    public function authenticate(Request $request): AuthContext
    {
        $header = $request->header('authorization');
        if ($header === null || !str_starts_with($header, 'Bearer ')) {
            throw new UnauthorizedException('Missing bearer token.');
        }

        $token = trim(substr($header, 7));

        try {
            $claims = $this->jwt->verify($token);
        } catch (JwtException $e) {
            throw new UnauthorizedException('Invalid token.', 0, $e);
        }

        $userId = $claims['sub'] ?? null;
        $organizationId = $claims['org'] ?? null;
        if (!is_string($userId) || !is_string($organizationId) || $userId === '' || $organizationId === '') {
            throw new UnauthorizedException('Token missing subject or organization.');
        }

        // Re-load scoped to the claimed tenant; cross-tenant claims cannot widen access.
        $user = $this->users->findByIdInOrganization($userId, $organizationId);
        if ($user === null || !$user->isActive()) {
            throw new UnauthorizedException('Principal could not be resolved.');
        }

        // Role claim is advisory; the stored role wins. Guard against unknown values.
        try {
            $claimedRole = is_string($claims['role'] ?? null) ? Role::from((string) $claims['role']) : null;
        } catch (ValueError) {
            throw new UnauthorizedException('Unknown role claim.');
        }
        if ($claimedRole !== null && $claimedRole !== $user->role) {
            throw new UnauthorizedException('Role claim no longer matches account.');
        }

        return AuthContext::fromUser($user);
    }
}
