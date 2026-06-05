<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Users;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Tenant\Auth\AuthContextResolver;
use NeneServe\Tenant\UseCase\ListUsersUseCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /admin/users (operationId `listUsers`). Requires `view_users`. Returns
 * only the caller's tenant — a cross-tenant (superadmin) principal excepted
 * (ADR 0006).
 */
final readonly class ListUsersHandler
{
    public function __construct(
        private ListUsersUseCase $listUsers,
        private JsonResponseFactory $response,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $context = AuthContextResolver::fromRequest($request);

        if ($context === null) {
            return $this->problemDetails->create($request, 'unauthorized', 'Unauthorized', 401, 'Authentication is required.');
        }

        return $this->response->create(['users' => $this->listUsers->execute($context)]);
    }
}
