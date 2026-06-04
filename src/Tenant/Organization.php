<?php

declare(strict_types=1);

namespace NeneServe\Tenant;

/**
 * Tenant root entity (ADR 0006). `organization_id` scopes every other table.
 */
final class Organization
{
    public function __construct(
        public readonly string $id,
        public readonly string $slug,
        public readonly string $name,
        public readonly string $defaultLocale = 'en',
        public readonly string $status = 'active',
    ) {
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
