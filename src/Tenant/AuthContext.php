<?php

declare(strict_types=1);

namespace NeneServe\Tenant;

/**
 * The resolved principal for an authenticated admin request: which user, which
 * tenant, which role. Produced by {@see \NeneServe\Http\Auth\BearerTokenMiddleware}
 * after the token is verified and the user re-loaded from storage (the DB role
 * — not the token claim — is authoritative).
 */
final class AuthContext
{
    public function __construct(
        public readonly string $userId,
        public readonly string $organizationId,
        public readonly Role $role,
    ) {
    }

    public static function fromUser(User $user): self
    {
        return new self($user->id, $user->organizationId, $user->role);
    }

    public function can(Capability $capability): bool
    {
        return $this->role->can($capability);
    }

    public function isCrossTenant(): bool
    {
        return $this->role->isCrossTenant();
    }
}
