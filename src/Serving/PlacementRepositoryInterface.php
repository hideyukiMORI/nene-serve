<?php

declare(strict_types=1);

namespace NeneServe\Serving;

interface PlacementRepositoryInterface
{
    /** Public serve lookup by the publisher-facing key (globally unique). */
    public function findByPublicKey(string $publicPlacementKey): ?Placement;

    /** Tenant-scoped lookup (admin/service surfaces). */
    public function findByIdInOrganization(string $id, string $organizationId): ?Placement;

    /**
     * @return list<Placement>
     */
    public function listByOrganization(string $organizationId): array;

    public function save(Placement $placement): void;
}
