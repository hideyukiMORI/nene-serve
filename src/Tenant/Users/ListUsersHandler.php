<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Users;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\PaginationQueryParser;
use Nene2\Http\PaginationResponse;
use NeneServe\Tenant\Auth\AuthContextResolver;
use NeneServe\Tenant\User;
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
        private ListUsersUseCaseInterface $listUsers,
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

        $pagination = PaginationQueryParser::parse($request);

        $output = $this->listUsers->execute(new ListUsersInput($context->isCrossTenant(), $pagination->limit, $pagination->offset));

        return $this->response->create(
            (new PaginationResponse(
                items: array_map(static fn (User $user): array => $user->toPublicArray(), $output->items),
                limit: $output->limit,
                offset: $output->offset,
            ))->toArray(),
        );
    }
}
