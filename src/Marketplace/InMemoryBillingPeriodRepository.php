<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

final class InMemoryBillingPeriodRepository implements BillingPeriodRepositoryInterface
{
    /** @var array<string, BillingPeriod> */
    private array $periods = [];

    /** @param list<BillingPeriod> $periods */
    public function __construct(array $periods = [])
    {
        foreach ($periods as $period) {
            $this->periods[$period->id] = $period;
        }
    }

    public function findByIdInOrganization(string $id, string $organizationId): ?BillingPeriod
    {
        $period = $this->periods[$id] ?? null;

        return ($period !== null && $period->organizationId === $organizationId) ? $period : null;
    }

    public function listByCampaign(string $organizationId, string $campaignId): array
    {
        return array_values(array_filter(
            $this->periods,
            static fn (BillingPeriod $p): bool => $p->organizationId === $organizationId && $p->campaignId === $campaignId,
        ));
    }

    public function save(BillingPeriod $period): void
    {
        $this->periods[$period->id] = $period;
    }
}
