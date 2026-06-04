<?php

declare(strict_types=1);

namespace NeneServe\Mcp;

final class InMemoryChangePlanRepository implements ChangePlanRepositoryInterface
{
    /** @var array<string, ChangePlan> */
    private array $plans = [];

    public function findByIdInOrganization(string $id, string $organizationId): ?ChangePlan
    {
        $plan = $this->plans[$id] ?? null;

        return ($plan !== null && $plan->organizationId === $organizationId) ? $plan : null;
    }

    public function save(ChangePlan $plan): void
    {
        $this->plans[$plan->id] = $plan;
    }
}
