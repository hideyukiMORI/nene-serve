<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Users;

use Nene2\Http\RequestScopedHolder;
use NeneServe\Tenant\UserRepositoryInterface;

/**
 * Lists operators visible to the caller. Tenant-scoped to the caller's
 * organization; only a cross-tenant (superadmin) principal sees every tenant
 * (ADR 0006, api-security §0.4). Capability gating happens at the route guard.
 */
final readonly class ListUsersUseCase implements ListUsersUseCaseInterface
{
    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private UserRepositoryInterface $users,
        private RequestScopedHolder $organizationId,
    ) {
    }

    public function execute(ListUsersInput $input): ListUsersOutput
    {
        $items = $input->crossTenant
            ? $this->users->listAll($input->limit, $input->offset)
            : $this->users->listByOrganization($this->organizationId->get(), $input->limit, $input->offset);

        return new ListUsersOutput($items, $input->limit, $input->offset);
    }
}
