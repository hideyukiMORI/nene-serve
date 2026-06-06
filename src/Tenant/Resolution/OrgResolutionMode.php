<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Resolution;

/**
 * How the admin surface determines the current tenant (ADR 0006). Selected by the
 * `TENANT_RESOLUTION` environment variable; unknown/empty values fall back to
 * {@see self::Login}, which preserves the original behaviour (the operator types
 * the organization slug at sign-in and it travels in the JWT `org` claim).
 *
 * The other modes derive the organization from the request URL and are reconciled
 * against the JWT by {@see \NeneServe\Tenant\Auth\AdminAuthMiddleware}.
 */
enum OrgResolutionMode: string
{
    case Login = 'login';
    case Single = 'single';
    case Subdomain = 'subdomain';
    case Path = 'path';
    case CustomDomain = 'custom_domain';

    public static function fromEnv(?string $value): self
    {
        return self::tryFrom($value !== null ? strtolower(trim($value)) : '') ?? self::Login;
    }

    /**
     * Whether this mode derives the tenant from the request URL (and therefore
     * needs {@see OrgResolverMiddleware} in the pipeline). Login mode does not.
     */
    public function usesUrlResolution(): bool
    {
        return $this !== self::Login;
    }

    /** Path mode carries the org as a leading URI segment that must be stripped. */
    public function stripsPathPrefix(): bool
    {
        return $this === self::Path;
    }
}
