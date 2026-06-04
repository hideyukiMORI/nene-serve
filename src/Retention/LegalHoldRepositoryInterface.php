<?php

declare(strict_types=1);

namespace NeneServe\Retention;

interface LegalHoldRepositoryInterface
{
    public function findByIdInOrganization(string $id, string $organizationId): ?LegalHold;

    public function hasActiveHold(string $organizationId): bool;

    public function save(LegalHold $hold): void;
}
