<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Tenant\AuthContext;
use NeneServe\Tenant\UseCase\ListUsersUseCase;

/**
 * GET /admin/users (operationId `listUsers`). Requires the `view_users`
 * capability (enforced by the route guard) and returns only the caller's
 * tenant — superadmin excepted (ADR 0006).
 */
final class ListUsersHandler
{
    public function __construct(
        private readonly ListUsersUseCase $listUsers,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        return $this->json->ok(['users' => $this->listUsers->execute($context)]);
    }
}
