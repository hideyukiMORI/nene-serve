<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Resolution;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Single-tenant resolution: every admin request belongs to one fixed
 * organization configured via `TENANT_ORG_SLUG`. Intended for a dedicated
 * install (one operator owns the whole instance) and for local development.
 *
 * Returns null when no slug is configured, so {@see OrgResolverMiddleware} fails
 * closed rather than guessing a tenant.
 */
final readonly class EnvResolutionStrategy implements OrgResolutionStrategyInterface
{
    public function __construct(
        private string $orgSlug,
    ) {
    }

    public function resolve(ServerRequestInterface $request): ?string
    {
        return $this->orgSlug !== '' ? $this->orgSlug : null;
    }
}
