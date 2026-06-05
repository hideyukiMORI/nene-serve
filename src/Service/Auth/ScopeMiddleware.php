<?php

declare(strict_types=1);

namespace NeneServe\Service\Auth;

use Nene2\Error\ProblemDetailsResponseFactory;
use NeneServe\Service\ServiceContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Enforces the per-route scope on the service surface (api-security §5). Runs
 * after {@see ServiceAuthMiddleware}: when a token context is present and the
 * route maps to a scope the token lacks, the request is refused with 403
 * insufficient-scope. Routes with no context (every non-`/api/*` surface) pass
 * through untouched.
 */
final readonly class ScopeMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $context = $request->getAttribute(ServiceAuthMiddleware::CONTEXT_ATTRIBUTE);

        if (!$context instanceof ServiceContext) {
            return $handler->handle($request);
        }

        $required = ScopeResolver::resolve($request->getUri()->getPath() ?: '/', $request->getMethod());

        if ($required !== null && !$context->hasScope($required)) {
            return $this->problemDetails->create(
                $request,
                'insufficient-scope',
                'Insufficient scope',
                403,
                sprintf('This action requires the %s scope.', $required->value),
            );
        }

        return $handler->handle($request);
    }
}
