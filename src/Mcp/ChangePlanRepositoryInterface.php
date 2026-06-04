<?php

declare(strict_types=1);

namespace NeneServe\Mcp;

interface ChangePlanRepositoryInterface
{
    public function findByIdInOrganization(string $id, string $organizationId): ?ChangePlan;

    public function save(ChangePlan $plan): void;
}
