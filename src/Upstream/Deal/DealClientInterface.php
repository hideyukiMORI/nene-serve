<?php

declare(strict_types=1);

namespace NeneServe\Upstream\Deal;

/**
 * Creates a sales opportunity in NeNe Deal from a high-value campaign
 * (sibling-products map, ADR 0002). Net amount only (no tax); idempotent on
 * `externalReference`. HTTP only.
 */
interface DealClientInterface
{
    /**
     * @param int $amountCents net budget, JPY minimum units (no tax)
     *
     * @throws DealClientException on transport failure (retryable)
     */
    public function createOpportunity(
        string $externalReference,
        string $advertiserName,
        string $campaignName,
        int $amountCents,
    ): DealOpportunityResult;
}
