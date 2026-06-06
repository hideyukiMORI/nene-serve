<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Resolution;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Tenant\Organization;
use NeneServe\Tenant\OrganizationRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Resolves the current tenant from the request URL for the admin surface when a
 * URL {@see OrgResolutionMode} is active (subdomain / path / custom domain /
 * single), writing it into the shared org-id holder and exposing it as a request
 * attribute for {@see \NeneServe\Tenant\Auth\AdminAuthMiddleware} to reconcile
 * against the JWT (ADR 0006 / ADR 0018).
 *
 * Fails closed on the admin surface: a request whose organization cannot be
 * resolved, is unknown, or is inactive is rejected (404/403). The global
 * surfaces — `/health`, the public serve surface (tenant via placement key) and
 * the service surface (tenant via scoped token) — always pass through untouched,
 * as do the open onboarding routes (`/admin/login`, `/admin/invitations`).
 *
 * In login mode this middleware is omitted from the pipeline entirely, so the
 * original JWT-only behaviour is unchanged.
 */
final readonly class OrgResolverMiddleware implements MiddlewareInterface
{
    public const string RESOLVED_ORG_ID_ATTRIBUTE = 'nene-serve.tenant.resolved_org_id';
    public const string RESOLVED_ORG_SLUG_ATTRIBUTE = 'nene-serve.tenant.resolved_org_slug';

    /** @var list<string> Surfaces whose tenant is never derived from the URL. */
    private const array GLOBAL_BYPASS = [
        '/health',
        '/public/',
        '/api/',
    ];

    /**
     * @param RequestScopedHolder<string> $organizationId shared holder written here
     *        and reconciled with the JWT by the admin auth middleware.
     */
    public function __construct(
        private RequestScopedHolder $organizationId,
        private OrganizationRepositoryInterface $organizations,
        private ProblemDetailsResponseFactory $problemDetails,
        private OrgResolutionStrategyInterface $strategy,
        private OrgResolutionMode $mode,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath() ?: '/';

        foreach (self::GLOBAL_BYPASS as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return $handler->handle($request);
            }
        }

        $slug = $this->strategy->resolve($request);

        // Path mode carries the org as a leading URI segment (/acme/admin/...).
        // Strip it so the router and downstream middleware see /admin/... .
        $effectivePath = $path;

        if ($this->mode->stripsPathPrefix()
            && $slug !== null
            && str_starts_with($path, '/' . $slug . '/')) {
            $effectivePath = substr($path, strlen('/' . $slug));
            $request = $request->withUri($request->getUri()->withPath($effectivePath));
        }

        // Only the admin (operator) surface is tenant-by-URL.
        if (!str_starts_with($effectivePath, '/admin/')) {
            return $handler->handle($request);
        }

        // Open bootstrap/onboarding routes carry their own org (login body, invite
        // token) or merely report it (tenant-context). Resolution there is
        // best-effort: attach the tenant when we can, but never fail closed — the
        // login screen must still load on a bare domain.
        if ($this->isOpenAdminRoute($effectivePath)) {
            $organization = $slug !== null ? $this->lookup($slug) : null;

            if ($organization !== null && $organization->isActive()) {
                $this->organizationId->set($organization->id);
                $request = $this->attach($request, $organization);
            }

            return $handler->handle($request);
        }

        if ($slug === null) {
            return $this->problem(
                $request,
                'org-not-resolved',
                'Organization Not Resolved',
                404,
                'Could not determine the organization for this request. Check the TENANT_RESOLUTION configuration.',
            );
        }

        $organization = $this->lookup($slug);

        if ($organization === null) {
            return $this->problem($request, 'org-not-found', 'Organization Not Found', 404, "No organization found for '{$slug}'.");
        }

        if (!$organization->isActive()) {
            return $this->problem($request, 'org-inactive', 'Organization Inactive', 403, 'This organization is currently inactive.');
        }

        $this->organizationId->set($organization->id);

        return $handler->handle($this->attach($request, $organization));
    }

    private function isOpenAdminRoute(string $path): bool
    {
        return $path === '/admin/login'
            || $path === '/admin/tenant-context'
            || str_starts_with($path, '/admin/invitations');
    }

    private function lookup(string $slug): ?Organization
    {
        return $this->organizations->findBySlug($slug)
            ?? $this->organizations->findByCustomDomain($slug);
    }

    private function attach(ServerRequestInterface $request, Organization $organization): ServerRequestInterface
    {
        return $request
            ->withAttribute(self::RESOLVED_ORG_ID_ATTRIBUTE, $organization->id)
            ->withAttribute(self::RESOLVED_ORG_SLUG_ATTRIBUTE, $organization->slug);
    }

    private function problem(
        ServerRequestInterface $request,
        string $type,
        string $title,
        int $status,
        string $detail,
    ): ResponseInterface {
        return $this->problemDetails->create($request, $type, $title, $status, $detail);
    }
}
