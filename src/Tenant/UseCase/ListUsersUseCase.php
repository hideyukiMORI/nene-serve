<?php

declare(strict_types=1);

namespace NeneServe\Tenant\UseCase;

use NeneServe\Tenant\AuthContext;
use NeneServe\Tenant\UserRepositoryInterface;

/**
 * Lists operators visible to the caller. Tenant-scoped to the caller's
 * organization; only a cross-tenant (superadmin) principal sees every tenant
 * (ADR 0006, api-security §0.4). Capability gating happens at the route guard.
 */
final class ListUsersUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {
    }

    /**
     * @return list<array{id: string, organization_id: string, email: string, role: string}>
     */
    public function execute(AuthContext $context): array
    {
        $users = $context->isCrossTenant()
            ? $this->users->listAll()
            : $this->users->listByOrganization($context->organizationId);

        return array_map(static fn ($u) => $u->toPublicArray(), $users);
    }
}
