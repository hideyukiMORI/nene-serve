<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

interface AdvertiserRepositoryInterface
{
    public function findByIdInOrganization(string $id, string $organizationId): ?Advertiser;

    /**
     * @return list<Advertiser>
     */
    public function listByOrganization(string $organizationId, int $limit, int $offset): array;

    public function save(Advertiser $advertiser): void;
}
