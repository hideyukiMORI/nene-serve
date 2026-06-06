<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Auth;

use Nene2\Auth\TokenVerificationException;
use Nene2\Auth\TokenVerifierInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Tenant\Resolution\OrgResolverMiddleware;
use NeneServe\Tenant\Role;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Authenticates admin API requests (ADR 0018). Verifies the Bearer token on
 * `/admin/*` routes and exposes the decoded claims as a request attribute for
 * {@see CapabilityMiddleware}. Open routes (login and invitation onboarding)
 * and the non-admin surfaces (`/health`, `/public/*`, `/api/*`) pass through.
 *
 * When a URL {@see \NeneServe\Tenant\Resolution\OrgResolutionMode} resolved the
 * tenant from the request (subdomain / path / custom domain / single), the token
 * is reconciled against it: a token minted for a different organization is
 * rejected (403), except for a cross-tenant superadmin operating within the
 * resolved tenant. In login mode no tenant is resolved from the URL and the JWT
 * `org` claim is authoritative, exactly as before.
 */
final readonly class AdminAuthMiddleware implements MiddlewareInterface
{
    public const string CLAIMS_ATTRIBUTE = 'nene-serve.auth.claims';

    /**
     * @param RequestScopedHolder<string> $organizationId carries the authenticated
     *        tenant for downstream admin use-cases (set here, read in the use-case).
     */
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
        private TokenVerifierInterface $verifier,
        private RequestScopedHolder $organizationId,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->requiresAuthentication($request)) {
            return $handler->handle($request);
        }

        $authorization = $request->getHeaderLine('Authorization');

        if ($authorization === '' || !str_starts_with($authorization, 'Bearer ')) {
            return $this->unauthorized($request, 'Authorization header must use the Bearer scheme.');
        }

        try {
            $claims = $this->verifier->verify(substr($authorization, 7));
        } catch (TokenVerificationException $e) {
            return $this->unauthorized($request, $e->getMessage());
        }

        $organizationId = $claims['org'] ?? null;
        $resolvedOrg = $request->getAttribute(OrgResolverMiddleware::RESOLVED_ORG_ID_ATTRIBUTE);

        if (is_string($resolvedOrg) && $resolvedOrg !== '') {
            // A URL resolution mode placed the tenant on the request. Reject a
            // token issued for a different organization (cross-tenant access),
            // unless the principal is a cross-tenant superadmin acting within the
            // resolved tenant. The resolved tenant is authoritative.
            $isSuperadmin = ($claims['role'] ?? null) === Role::SuperAdmin->value;

            if (!$isSuperadmin && (!is_string($organizationId) || $organizationId !== $resolvedOrg)) {
                return $this->forbidden($request, 'This token is not valid for the requested organization.');
            }

            $this->organizationId->set($resolvedOrg);
        } elseif (is_string($organizationId) && $organizationId !== '') {
            $this->organizationId->set($organizationId);
        }

        return $handler->handle($request->withAttribute(self::CLAIMS_ATTRIBUTE, $claims));
    }

    private function requiresAuthentication(ServerRequestInterface $request): bool
    {
        $path = $request->getUri()->getPath() ?: '/';

        if (!str_starts_with($path, '/admin/')) {
            return false;
        }

        // Open onboarding routes: login and invitation preview/accept.
        if ($path === '/admin/login' || str_starts_with($path, '/admin/invitations')) {
            return false;
        }

        return true;
    }

    private function unauthorized(ServerRequestInterface $request, string $detail): ResponseInterface
    {
        return $this->problemDetails->create(
            $request,
            'unauthorized',
            'Unauthorized',
            401,
            $detail,
        );
    }

    private function forbidden(ServerRequestInterface $request, string $detail): ResponseInterface
    {
        return $this->problemDetails->create(
            $request,
            'organization-mismatch',
            'Forbidden',
            403,
            $detail,
        );
    }
}
