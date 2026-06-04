<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

final class InMemoryDealOpportunityRepository implements DealOpportunityRepositoryInterface
{
    /** @var array<string, DealOpportunity> keyed by externalReference */
    private array $opportunities = [];

    public function findByExternalReference(string $organizationId, string $externalReference): ?DealOpportunity
    {
        $o = $this->opportunities[$externalReference] ?? null;

        return ($o !== null && $o->organizationId === $organizationId) ? $o : null;
    }

    public function save(DealOpportunity $opportunity): void
    {
        $this->opportunities[$opportunity->externalReference] = $opportunity;
    }
}
