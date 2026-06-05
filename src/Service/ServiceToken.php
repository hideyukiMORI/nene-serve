<?php

declare(strict_types=1);

namespace NeneServe\Service;

/**
 * An opaque service token bound to one tenant and a set of scopes. The raw
 * secret is never returned by the API; it is matched by hash at the boundary.
 */
final class ServiceToken
{
    /** @param list<Scope> $scopes */
    public function __construct(
        public readonly string $id,
        public readonly string $organizationId,
        public readonly string $tokenHash,
        public readonly array $scopes,
        public readonly string $status = 'active',
    ) {
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function context(): ServiceContext
    {
        return new ServiceContext($this->organizationId, $this->scopes);
    }
}
