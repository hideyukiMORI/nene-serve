<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Resolution;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Path-prefix (directory) resolution: `/acme/admin/...` → `acme`. Best for
 * shared hosts where wildcard subdomains are not available.
 *
 * {@see OrgResolverMiddleware} strips the resolved prefix from the request URI
 * before the router runs, so downstream handlers and route specs still see
 * `/admin/...`. The global surfaces (`/health`, `/public/*`, `/api/*`) are never
 * org-prefixed and resolve to null here.
 */
final readonly class PathPrefixResolutionStrategy implements OrgResolutionStrategyInterface
{
    /** @var list<string> Unprefixed global surfaces that carry no org segment. */
    private const array GLOBAL_PREFIXES = [
        '/health',
        '/public/',
        '/api/',
    ];

    public function resolve(ServerRequestInterface $request): ?string
    {
        $path = $request->getUri()->getPath();

        foreach (self::GLOBAL_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return null;
            }
        }

        $segments = explode('/', ltrim($path, '/'), 2);
        $slug = $segments[0];

        return $slug !== '' ? $slug : null;
    }
}
