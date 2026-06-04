<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

/** Derived, reproducible spend for a campaign (billing §3.2/§3.3). */
final class CampaignSpend
{
    public function __construct(
        public readonly int $impressions,
        public readonly int $clicks,
        public readonly int $spentCents,
        public readonly int $budgetCents,
        public readonly int $pricingRuleVersion,
    ) {
    }

    public function isExhausted(): bool
    {
        return $this->spentCents >= $this->budgetCents;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'billable_impressions' => $this->impressions,
            'billable_clicks' => $this->clicks,
            'spent_cents' => $this->spentCents,
            'budget_cents' => $this->budgetCents,
            'currency' => 'JPY',
            'pricing_rule_version' => $this->pricingRuleVersion,
            'exhausted' => $this->isExhausted(),
        ];
    }
}
