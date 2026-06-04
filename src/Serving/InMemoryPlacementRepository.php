<?php

declare(strict_types=1);

namespace NeneServe\Serving;

final class InMemoryPlacementRepository implements PlacementRepositoryInterface
{
    /** @var list<Placement> */
    private array $placements;

    /** @param list<Placement> $placements */
    public function __construct(array $placements = [])
    {
        $this->placements = $placements;
    }

    public function findByPublicKey(string $publicPlacementKey): ?Placement
    {
        foreach ($this->placements as $p) {
            if ($p->publicPlacementKey === $publicPlacementKey) {
                return $p;
            }
        }

        return null;
    }

    public function findByIdInOrganization(string $id, string $organizationId): ?Placement
    {
        foreach ($this->placements as $p) {
            if ($p->id === $id && $p->organizationId === $organizationId) {
                return $p;
            }
        }

        return null;
    }

    public function listByOrganization(string $organizationId): array
    {
        return array_values(array_filter(
            $this->placements,
            static fn (Placement $p): bool => $p->organizationId === $organizationId,
        ));
    }
}
