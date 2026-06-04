<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

/**
 * A versioned, tamper-evident record of a billing period's spend (billing
 * §3.2/§3.3/§7). Stores the inputs so the figure is **reproducible**
 * (`spent = f(billable_units, pricing_rule_version)`); `hash` lets a reviewer
 * detect any silent mutation. Snapshots are **append-only** — re-deriving writes
 * a new `version`, never an overwrite.
 */
final class SpendSnapshot
{
    public function __construct(
        public readonly string $id,
        public readonly string $organizationId,
        public readonly string $billingPeriodId,
        public readonly int $version,
        public readonly int $billableImpressions,
        public readonly int $billableClicks,
        public readonly string $pricingRuleId,
        public readonly int $pricingRuleVersion,
        public readonly int $spentCents,
        public readonly string $hash,
        public readonly string $createdAt,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'billing_period_id' => $this->billingPeriodId,
            'version' => $this->version,
            'billable_impressions' => $this->billableImpressions,
            'billable_clicks' => $this->billableClicks,
            'pricing_rule_id' => $this->pricingRuleId,
            'pricing_rule_version' => $this->pricingRuleVersion,
            'spent_cents' => $this->spentCents,
            'currency' => 'JPY',
            'hash' => $this->hash,
            'created_at' => $this->createdAt,
        ];
    }
}
