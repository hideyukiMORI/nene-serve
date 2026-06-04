<?php

declare(strict_types=1);

namespace NeneServe\Service;

/** Resolved service-token principal: tenant + granted scopes. */
final class ServiceContext
{
    /** @param list<Scope> $scopes */
    public function __construct(
        public readonly string $organizationId,
        public readonly array $scopes,
    ) {
    }

    public function hasScope(Scope $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }
}
