<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

interface BillingPeriodRepositoryInterface
{
    public function findByIdInOrganization(string $id, string $organizationId): ?BillingPeriod;

    public function save(BillingPeriod $period): void;
}
