<?php

declare(strict_types=1);

namespace NeneServe\Tenant;

/**
 * User persistence. **Tenant isolation is absolute** (ADR 0006, api-security
 * §0.4): the only lookups that span organizations are the explicit cross-tenant
 * methods, which callers must gate behind a `superadmin` {@see AuthContext}.
 */
interface UserRepositoryInterface
{
    /** Lookup scoped to one organization. Returns null if absent or cross-tenant. */
    public function findByIdInOrganization(string $userId, string $organizationId): ?User;

    /** Login lookup, scoped to one organization. */
    public function findByEmailInOrganization(string $email, string $organizationId): ?User;

    /** Create or update a user (invitation provisioning, password set). Never deletes. */
    public function save(User $user): void;

    /**
     * All users in one organization.
     *
     * @return list<User>
     */
    public function listByOrganization(string $organizationId, int $limit, int $offset): array;

    /**
     * Cross-tenant lookup — superadmin only. Callers MUST verify
     * {@see AuthContext::isCrossTenant()} before calling.
     */
    public function findByIdAcrossTenants(string $userId): ?User;

    /**
     * Cross-tenant listing — superadmin only.
     *
     * @return list<User>
     */
    public function listAll(int $limit, int $offset): array;
}
