<?php

declare(strict_types=1);

namespace NeneServe\Tests\Marketplace;

use NeneServe\Marketplace\Campaign;
use NeneServe\Marketplace\CampaignRepositoryInterface;

/** In-memory {@see CampaignRepositoryInterface} double for use-case unit tests. */
final class InMemoryCampaignRepository implements CampaignRepositoryInterface
{
    /** @var array<string, Campaign> */
    private array $campaigns = [];

    /** @param list<Campaign> $campaigns */
    public function __construct(array $campaigns = [])
    {
        foreach ($campaigns as $campaign) {
            $this->campaigns[$campaign->id] = $campaign;
        }
    }

    public function findByIdInOrganization(string $id, string $organizationId): ?Campaign
    {
        $campaign = $this->campaigns[$id] ?? null;

        return ($campaign !== null && $campaign->organizationId === $organizationId) ? $campaign : null;
    }

    public function listByOrganization(string $organizationId, int $limit, int $offset): array
    {
        $matches = array_values(array_filter(
            $this->campaigns,
            static fn (Campaign $c): bool => $c->organizationId === $organizationId,
        ));

        return array_slice($matches, $offset, $limit);
    }

    public function save(Campaign $campaign): void
    {
        $this->campaigns[$campaign->id] = $campaign;
    }
}
