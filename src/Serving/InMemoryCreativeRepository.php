<?php

declare(strict_types=1);

namespace NeneServe\Serving;

final class InMemoryCreativeRepository implements CreativeRepositoryInterface
{
    /** @var array<string, Creative> keyed by creative id */
    private array $creatives = [];

    /** @param list<Creative> $creatives */
    public function __construct(array $creatives = [])
    {
        foreach ($creatives as $creative) {
            $this->creatives[$creative->id] = $creative;
        }
    }

    public function findByIdInOrganization(string $id, string $organizationId): ?Creative
    {
        $creative = $this->creatives[$id] ?? null;

        return ($creative !== null && $creative->organizationId === $organizationId) ? $creative : null;
    }

    public function listByOrganization(string $organizationId): array
    {
        return array_values(array_filter(
            $this->creatives,
            static fn (Creative $c): bool => $c->organizationId === $organizationId,
        ));
    }

    public function save(Creative $creative): void
    {
        $this->creatives[$creative->id] = $creative;
    }
}
