<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

interface CampaignRepositoryInterface
{
    public function findByIdInOrganization(string $id, string $organizationId): ?Campaign;

    /**
     * @return list<Campaign>
     */
    public function listByOrganization(string $organizationId, int $limit, int $offset): array;

    public function save(Campaign $campaign): void;
}
