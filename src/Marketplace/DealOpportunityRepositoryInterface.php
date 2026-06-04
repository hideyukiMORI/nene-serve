<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

interface DealOpportunityRepositoryInterface
{
    public function findByExternalReference(string $organizationId, string $externalReference): ?DealOpportunity;

    public function save(DealOpportunity $opportunity): void;
}
