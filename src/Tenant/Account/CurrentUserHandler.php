<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Account;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Tenant\Auth\AuthContextResolver;
use NeneServe\Tenant\Capability;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /admin/me (operationId `getCurrentUser`). Returns the authenticated
 * principal and its effective capabilities. Any authenticated role may call it;
 * the user is re-loaded so the stored role — not the token claim — is
 * authoritative (ADR 0006).
 */
final readonly class CurrentUserHandler
{
    public function __construct(
        private GetCurrentUserUseCaseInterface $useCase,
        private JsonResponseFactory $response,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $context = AuthContextResolver::require($request);

        $user = $this->useCase->execute(new GetCurrentUserInput($context->userId))->user;

        if ($user === null) {
            return $this->problemDetails->create($request, 'unauthorized', 'Unauthorized', 401, 'Authentication failed.');
        }

        return $this->response->create([
            'user' => $user->toPublicArray(),
            'capabilities' => array_map(
                static fn (Capability $capability): string => $capability->value,
                $context->role->capabilities(),
            ),
            'cross_tenant' => $context->isCrossTenant(),
        ]);
    }
}
