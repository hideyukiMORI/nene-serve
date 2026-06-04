<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

/**
 * A marketplace campaign: an advertiser's funded delivery with a budget and a
 * versioned pricing rule (domain-model Phase 3+). `budgetCents` is net integer
 * money (no tax). Only an approved creative in an **active + funded** campaign
 * within budget serves and is billable (billing §3.1).
 */
final class Campaign
{
    public function __construct(
        public readonly string $id,
        public readonly string $organizationId,
        public readonly string $advertiserId,
        public readonly string $name,
        public readonly string $pricingRuleId,
        public readonly int $budgetCents,
        public readonly string $status = 'draft',
        public readonly FundingStatus $fundingStatus = FundingStatus::Unfunded,
        public readonly bool $pauseOnBudgetExhausted = true,
        public readonly ?string $archivedAt = null,
    ) {
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->archivedAt === null;
    }

    /** Eligible to fund delivery: active and funded (exhaustion checked separately). */
    public function isFundedForServe(): bool
    {
        return $this->isActive() && $this->fundingStatus === FundingStatus::Funded;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'advertiser_id' => $this->advertiserId,
            'name' => $this->name,
            'pricing_rule_id' => $this->pricingRuleId,
            'budget_cents' => $this->budgetCents,
            'currency' => 'JPY',
            'status' => $this->status,
            'funding_status' => $this->fundingStatus->value,
            'pause_on_budget_exhausted' => $this->pauseOnBudgetExhausted,
        ];
    }
}
