<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

final class InMemoryPricingRuleRepository implements PricingRuleRepositoryInterface
{
    /** @var array<string, PricingRule> */
    private array $rules = [];

    /** @param list<PricingRule> $rules */
    public function __construct(array $rules = [])
    {
        foreach ($rules as $rule) {
            $this->rules[$rule->id] = $rule;
        }
    }

    public function findByIdInOrganization(string $id, string $organizationId): ?PricingRule
    {
        $rule = $this->rules[$id] ?? null;

        return ($rule !== null && $rule->organizationId === $organizationId) ? $rule : null;
    }

    public function listByOrganization(string $organizationId): array
    {
        return array_values(array_filter(
            $this->rules,
            static fn (PricingRule $r): bool => $r->organizationId === $organizationId,
        ));
    }

    public function currentVersion(string $organizationId, string $name): int
    {
        $max = 0;
        foreach ($this->rules as $rule) {
            if ($rule->organizationId === $organizationId && $rule->name === $name) {
                $max = max($max, $rule->version);
            }
        }

        return $max;
    }

    public function save(PricingRule $rule): void
    {
        $this->rules[$rule->id] = $rule;
    }
}
