<?php

declare(strict_types=1);

namespace NeneServe\Serving;

final class InMemoryPlacementRepository implements PlacementRepositoryInterface
{
    /** @var array<string, Placement> keyed by placement id */
    private array $placements = [];

    /** @param list<Placement> $placements */
    public function __construct(array $placements = [])
    {
        foreach ($placements as $placement) {
            $this->placements[$placement->id] = $placement;
        }
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
        $p = $this->placements[$id] ?? null;

        return ($p !== null && $p->organizationId === $organizationId) ? $p : null;
    }

    public function listByOrganization(string $organizationId, int $limit, int $offset): array
    {
        $matches = array_values(array_filter(
            $this->placements,
            static fn (Placement $p): bool => $p->organizationId === $organizationId,
        ));

        return array_slice($matches, $offset, $limit);
    }

    public function save(Placement $placement): void
    {
        $this->placements[$placement->id] = $placement;
    }

    public function archive(string $id, string $organizationId, string $at): void
    {
        $placement = $this->findByIdInOrganization($id, $organizationId);
        if ($placement !== null) {
            $this->placements[$id] = $placement->archive($at);
        }
    }
}
