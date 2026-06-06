<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Resolution;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Subdomain resolution: `acme.serve.example.com` → `acme`, given
 * `TENANT_BASE_DOMAIN=serve.example.com`. Requests to the bare base domain
 * (no leading label) resolve to null.
 */
final readonly class SubdomainResolutionStrategy implements OrgResolutionStrategyInterface
{
    public function __construct(
        private string $baseDomain,
    ) {
    }

    public function resolve(ServerRequestInterface $request): ?string
    {
        $host = $this->hostWithoutPort($request);

        if ($host === '' || $this->baseDomain === '') {
            return null;
        }

        $baseParts = explode('.', strtolower($this->baseDomain));
        $hostParts = explode('.', $host);

        // A subdomain exists only when the host has more labels than the base.
        if (count($hostParts) <= count($baseParts)) {
            return null;
        }

        // The trailing labels must match the configured base domain exactly.
        $tail = array_slice($hostParts, -count($baseParts));

        if ($tail !== $baseParts) {
            return null;
        }

        $slug = $hostParts[0];

        return $slug !== '' ? $slug : null;
    }

    private function hostWithoutPort(ServerRequestInterface $request): string
    {
        $host = $request->getUri()->getHost();

        if (str_contains($host, ':')) {
            $host = explode(':', $host)[0];
        }

        return strtolower($host);
    }
}
