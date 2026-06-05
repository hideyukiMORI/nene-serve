<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

interface PricingRuleRepositoryInterface
{
    public function findByIdInOrganization(string $id, string $organizationId): ?PricingRule;

    /**
     * @return list<PricingRule>
     */
    public function listByOrganization(string $organizationId, int $limit, int $offset): array;

    /** Highest existing version for a logical rule name in the org (0 if none). */
    public function currentVersion(string $organizationId, string $name): int;

    /** Append a new immutable pricing-rule version. */
    public function save(PricingRule $rule): void;
}
