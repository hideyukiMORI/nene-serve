<?php

declare(strict_types=1);

namespace NeneServe\Tests\Marketplace;

use NeneServe\Marketplace\PricingRule;
use NeneServe\Marketplace\PricingRuleRepositoryInterface;

/** In-memory {@see PricingRuleRepositoryInterface} double for use-case unit tests. */
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

    public function listByOrganization(string $organizationId, int $limit, int $offset): array
    {
        $matches = array_values(array_filter(
            $this->rules,
            static fn (PricingRule $r): bool => $r->organizationId === $organizationId,
        ));

        return array_slice($matches, $offset, $limit);
    }

    public function currentVersion(string $organizationId, string $name): int
    {
        $version = 0;
        foreach ($this->rules as $rule) {
            if ($rule->organizationId === $organizationId && $rule->name === $name) {
                $version = max($version, $rule->version);
            }
        }

        return $version;
    }

    public function save(PricingRule $rule): void
    {
        $this->rules[$rule->id] = $rule;
    }
}
