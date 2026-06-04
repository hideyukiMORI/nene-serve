<?php

declare(strict_types=1);

namespace NeneServe\Tenant;

/**
 * Operator account, always bound to exactly one organization (ADR 0006).
 * The password hash never leaves the persistence boundary in API responses.
 */
final class User
{
    public function __construct(
        public readonly string $id,
        public readonly string $organizationId,
        public readonly string $email,
        public readonly Role $role,
        public readonly string $passwordHash,
        public readonly string $status = 'active',
    ) {
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function verifyPassword(string $plain): bool
    {
        // An empty hash (invited, not yet set) never matches — login stays closed
        // until the invitation is accepted and a password is set.
        return $this->passwordHash !== '' && password_verify($plain, $this->passwordHash);
    }

    /** New instance with the password set (invitation acceptance / reset). */
    public function withPasswordHash(string $passwordHash): self
    {
        return new self($this->id, $this->organizationId, $this->email, $this->role, $passwordHash, $this->status);
    }

    /**
     * API-safe projection — never exposes the password hash (api-security-spec §2).
     *
     * @return array{id: string, organization_id: string, email: string, role: string}
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organizationId,
            'email' => $this->email,
            'role' => $this->role->value,
        ];
    }
}
