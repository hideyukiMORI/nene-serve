<?php

declare(strict_types=1);

namespace NeneServe\Auth;

use Nene2\Http\JsonResponseFactory;
use NeneServe\Tenant\OrganizationRepositoryInterface;
use NeneServe\Tenant\Resolution\OrgResolutionMode;
use NeneServe\Tenant\Resolution\OrgResolverMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /admin/tenant-context (operationId `tenantContext`). Unauthenticated
 * bootstrap endpoint that tells the admin SPA how the tenant is determined
 * (ADR 0006), so the login screen can show the organization field only in
 * `login` mode and otherwise present the resolved organization.
 *
 * In the URL modes {@see OrgResolverMiddleware} has already attached the resolved
 * tenant (best-effort) as a request attribute; in `login` mode none is attached
 * and the organization is null.
 */
final readonly class TenantContextHandler
{
    public function __construct(
        private OrgResolutionMode $mode,
        private OrganizationRepositoryInterface $organizations,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $organization = null;
        $resolvedId = $request->getAttribute(OrgResolverMiddleware::RESOLVED_ORG_ID_ATTRIBUTE);

        if (is_string($resolvedId) && $resolvedId !== '') {
            $resolved = $this->organizations->findById($resolvedId);

            if ($resolved !== null) {
                $organization = [
                    'slug' => $resolved->slug,
                    'name' => $resolved->name,
                ];
            }
        }

        return $this->response->create([
            'mode' => $this->mode->value,
            'organization' => $organization,
        ]);
    }
}
