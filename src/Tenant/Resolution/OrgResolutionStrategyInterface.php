<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Resolution;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Resolves the organization identifier (slug or custom domain) for an incoming
 * admin request, so a single install can run one or many tenants without code
 * changes (ADR 0006). Implementations cover the supported {@see OrgResolutionMode}
 * URL strategies:
 *
 *  - {@see EnvResolutionStrategy}       — fixed `TENANT_ORG_SLUG` (single-tenant install)
 *  - {@see SubdomainResolutionStrategy} — `acme.serve.example.com` → `acme`
 *  - {@see PathPrefixResolutionStrategy} — `/acme/admin/...` → `acme`
 *  - {@see CustomDomainResolutionStrategy} — `acme.example.com` → looked up by custom domain
 *
 * Returns null when this strategy cannot determine an organization from the
 * request; {@see OrgResolverMiddleware} then fails closed on the admin surface.
 */
interface OrgResolutionStrategyInterface
{
    public function resolve(ServerRequestInterface $request): ?string;
}
