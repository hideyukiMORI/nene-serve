<?php

declare(strict_types=1);

namespace NeneServe\Service\Auth;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Service\ServiceTokenRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Authenticates the service surface (api-security §5). Resolves a
 * {@see \NeneServe\Service\ServiceContext} from an opaque `Authorization: Bearer
 * <service-token>` on `/api/*` and exposes it as a request attribute for
 * {@see ScopeMiddleware}. Fail closed: missing/unknown/inactive token → 401.
 * Every other surface (`/admin/*`, `/public/*`, `/health`) passes through.
 */
final readonly class ServiceAuthMiddleware implements MiddlewareInterface
{
    public const string CONTEXT_ATTRIBUTE = 'nene-serve.service.context';

    /**
     * @param RequestScopedHolder<string> $organizationId carries the token's tenant
     *        for downstream service use-cases (set here, read in the use-case).
     */
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
        private ServiceTokenRepositoryInterface $tokens,
        private RequestScopedHolder $organizationId,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath() ?: '/';

        if (!str_starts_with($path, '/api/')) {
            return $handler->handle($request);
        }

        $authorization = $request->getHeaderLine('Authorization');

        if ($authorization === '' || !str_starts_with($authorization, 'Bearer ')) {
            return $this->unauthorized($request, 'A service token is required.');
        }

        $token = $this->tokens->findByPresentedToken(trim(substr($authorization, 7)));

        if ($token === null) {
            return $this->unauthorized($request, 'The service token is invalid or inactive.');
        }

        $context = $token->context();
        $this->organizationId->set($context->organizationId);

        return $handler->handle($request->withAttribute(self::CONTEXT_ATTRIBUTE, $context));
    }

    private function unauthorized(ServerRequestInterface $request, string $detail): ResponseInterface
    {
        return $this->problemDetails->create($request, 'unauthorized', 'Unauthorized', 401, $detail);
    }
}
