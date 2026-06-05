<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Auth;

use Nene2\Error\ProblemDetailsResponseFactory;
use NeneServe\Tenant\Role;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Enforces fine-grained capabilities on authenticated admin routes (ADR 0006 /
 * ADR 0018). Runs after {@see AdminAuthMiddleware}; unauthenticated requests
 * (no claims attribute) pass through unchanged so open routes keep working.
 */
final readonly class CapabilityMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $claims = $request->getAttribute(AdminAuthMiddleware::CLAIMS_ATTRIBUTE);

        if (!is_array($claims)) {
            return $handler->handle($request);
        }

        $required = CapabilityResolver::resolve($request->getUri()->getPath() ?: '/', $request->getMethod());

        if ($required === null) {
            return $handler->handle($request);
        }

        $role = Role::tryFrom(is_string($claims['role'] ?? null) ? $claims['role'] : '');

        if ($role === null || !$role->can($required)) {
            return $this->problemDetails->create(
                $request,
                'forbidden',
                'Forbidden',
                403,
                'You do not have permission to perform this action.',
            );
        }

        return $handler->handle($request);
    }
}
