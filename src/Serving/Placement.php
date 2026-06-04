<?php

declare(strict_types=1);

namespace NeneServe\Serving;

/**
 * An ad slot on a publisher site. The `publicPlacementKey` is a public,
 * non-secret identifier used by `serve.js`; `allowedOrigins` gates the public
 * surface (ADR 0010, api-security §2).
 */
final class Placement
{
    /** @param list<string> $allowedOrigins */
    public function __construct(
        public readonly string $id,
        public readonly string $organizationId,
        public readonly string $publicPlacementKey,
        public readonly array $allowedOrigins,
        public readonly string $status = 'active',
        public readonly ?string $defaultCreativeId = null,
    ) {
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * True when the request origin is permitted. An empty allowlist permits any
     * origin (publisher opted out of origin gating); a present origin must match.
     */
    public function allowsOrigin(?string $origin): bool
    {
        if ($this->allowedOrigins === [] || $origin === null) {
            return true;
        }

        return in_array($origin, $this->allowedOrigins, true);
    }
}
