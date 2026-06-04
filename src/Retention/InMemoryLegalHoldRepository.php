<?php

declare(strict_types=1);

namespace NeneServe\Retention;

final class InMemoryLegalHoldRepository implements LegalHoldRepositoryInterface
{
    /** @var array<string, LegalHold> */
    private array $holds = [];

    /** @param list<LegalHold> $holds */
    public function __construct(array $holds = [])
    {
        foreach ($holds as $hold) {
            $this->holds[$hold->id] = $hold;
        }
    }

    public function findByIdInOrganization(string $id, string $organizationId): ?LegalHold
    {
        $hold = $this->holds[$id] ?? null;

        return ($hold !== null && $hold->organizationId === $organizationId) ? $hold : null;
    }

    public function hasActiveHold(string $organizationId): bool
    {
        foreach ($this->holds as $hold) {
            if ($hold->organizationId === $organizationId && $hold->isActive()) {
                return true;
            }
        }

        return false;
    }

    public function save(LegalHold $hold): void
    {
        $this->holds[$hold->id] = $hold;
    }
}
