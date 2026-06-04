<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

final class InMemoryAdvertiserRepository implements AdvertiserRepositoryInterface
{
    /** @var array<string, Advertiser> */
    private array $advertisers = [];

    /** @param list<Advertiser> $advertisers */
    public function __construct(array $advertisers = [])
    {
        foreach ($advertisers as $advertiser) {
            $this->advertisers[$advertiser->id] = $advertiser;
        }
    }

    public function findByIdInOrganization(string $id, string $organizationId): ?Advertiser
    {
        $advertiser = $this->advertisers[$id] ?? null;

        return ($advertiser !== null && $advertiser->organizationId === $organizationId) ? $advertiser : null;
    }

    public function listByOrganization(string $organizationId): array
    {
        return array_values(array_filter(
            $this->advertisers,
            static fn (Advertiser $a): bool => $a->organizationId === $organizationId,
        ));
    }

    public function save(Advertiser $advertiser): void
    {
        $this->advertisers[$advertiser->id] = $advertiser;
    }
}
