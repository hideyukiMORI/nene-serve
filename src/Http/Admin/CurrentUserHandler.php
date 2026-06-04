<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Tenant\AuthContext;
use NeneServe\Tenant\UserRepositoryInterface;

/**
 * GET /admin/me (operationId `getCurrentUser`). Returns the authenticated
 * principal and its effective capabilities. Any authenticated role may call it.
 */
final class CurrentUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        $user = $this->users->findByIdInOrganization($context->userId, $context->organizationId);
        if ($user === null) {
            // Should not happen post-authentication, but fail closed regardless.
            return $this->json->problem(401, 'unauthorized', 'Authentication failed');
        }

        return $this->json->ok([
            'user' => $user->toPublicArray(),
            'capabilities' => array_map(
                static fn ($c) => $c->value,
                $context->role->capabilities(),
            ),
            'cross_tenant' => $context->isCrossTenant(),
        ]);
    }
}
