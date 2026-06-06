<?php

declare(strict_types=1);

namespace NeneServe\Tenant;

interface OrganizationRepositoryInterface
{
    public function findById(string $id): ?Organization;

    public function findBySlug(string $slug): ?Organization;

    /**
     * Resolves a tenant by its custom domain (custom-domain resolution mode,
     * ADR 0006). Returns null when no organization claims the domain.
     */
    public function findByCustomDomain(string $domain): ?Organization;
}
