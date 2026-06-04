<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

/**
 * Record of a campaign handed to NeNe Deal as a sales opportunity. Idempotent on
 * `externalReference` (one opportunity per campaign handoff). amount is net
 * integer money (no tax). Only `status`/`opportunityId` advance.
 */
final class DealOpportunity
{
    public function __construct(
        public readonly string $id,
        public readonly string $organizationId,
        public readonly string $campaignId,
        public readonly string $externalReference,
        public readonly int $amountCents,
        public readonly string $status,
        public readonly ?string $opportunityId,
        public readonly string $createdAt,
    ) {
    }

    public function withResult(string $status, ?string $opportunityId): self
    {
        return new self(
            $this->id,
            $this->organizationId,
            $this->campaignId,
            $this->externalReference,
            $this->amountCents,
            $status,
            $opportunityId,
            $this->createdAt,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'campaign_id' => $this->campaignId,
            'external_reference' => $this->externalReference,
            'amount_cents' => $this->amountCents,
            'currency' => 'JPY',
            'status' => $this->status,
            'opportunity_id' => $this->opportunityId,
        ];
    }
}
