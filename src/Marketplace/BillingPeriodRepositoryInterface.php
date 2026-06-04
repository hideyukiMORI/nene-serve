<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

interface BillingPeriodRepositoryInterface
{
    public function findByIdInOrganization(string $id, string $organizationId): ?BillingPeriod;

    /**
     * @return list<BillingPeriod>
     */
    public function listByCampaign(string $organizationId, string $campaignId): array;

    public function save(BillingPeriod $period): void;
}
