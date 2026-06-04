<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

use NeneServe\Money\Money;

/**
 * The versioned rule that converts billable units into net money (billing §3.3,
 * reproducibility). **Immutable**: a change creates a new `version` (a new row),
 * never an in-place edit, so any past figure stays reproducible as
 * `amount = f(billable_units, pricing_rule_version)`.
 */
final class PricingRule
{
    public function __construct(
        public readonly string $id,
        public readonly string $organizationId,
        public readonly string $name,
        public readonly PricingModel $model,
        public readonly int $rateCents,
        public readonly int $version,
        public readonly string $createdAt,
    ) {
    }

    public function rate(): Money
    {
        return Money::fromCents($this->rateCents);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'pricing_model' => $this->model->value,
            'rate_cents' => $this->rateCents,
            'currency' => Money::CURRENCY,
            'pricing_rule_version' => $this->version,
            'created_at' => $this->createdAt,
        ];
    }
}
