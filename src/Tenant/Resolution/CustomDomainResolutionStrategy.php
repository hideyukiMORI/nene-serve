<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Resolution;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Custom-domain resolution: a tenant points its own domain (CNAME) at this
 * install and is looked up by that domain. The raw Host header is returned;
 * {@see OrgResolverMiddleware} resolves it via
 * {@see \NeneServe\Tenant\OrganizationRepositoryInterface::findByCustomDomain()}.
 */
final readonly class CustomDomainResolutionStrategy implements OrgResolutionStrategyInterface
{
    public function resolve(ServerRequestInterface $request): ?string
    {
        $host = $request->getUri()->getHost();

        if (str_contains($host, ':')) {
            $host = explode(':', $host)[0];
        }

        $host = strtolower($host);

        return $host !== '' ? $host : null;
    }
}
