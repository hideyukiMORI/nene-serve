<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

/**
 * A campaign's billing window (billing §3.2). Spend accrues while `open`; once
 * `closed` its figures (the SpendSnapshot) are **immutable** — corrections are
 * additive adjustments in a later period, never edits. Status advances
 * open → closed → reconciled → handed_off (the latter two in #50).
 */
final class BillingPeriod
{
    public function __construct(
        public readonly string $id,
        public readonly string $organizationId,
        public readonly string $campaignId,
        public readonly string $periodStart,
        public readonly string $periodEnd,
        public readonly string $status = 'open',
    ) {
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function withStatus(string $status): self
    {
        return new self($this->id, $this->organizationId, $this->campaignId, $this->periodStart, $this->periodEnd, $status);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'campaign_id' => $this->campaignId,
            'period_start' => $this->periodStart,
            'period_end' => $this->periodEnd,
            'status' => $this->status,
        ];
    }
}
