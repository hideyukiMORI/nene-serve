<?php

declare(strict_types=1);

namespace NeneServe\Upstream\Deal;

/**
 * In-memory Deal client for boot/tests. **Idempotent on externalReference** (a
 * retry returns the same opportunity, no duplicate). Optional failing mode
 * exercises transport-failure isolation.
 */
final class FakeDealClient implements DealClientInterface
{
    /** @var array<string, DealOpportunityResult> keyed by externalReference */
    private array $opportunities = [];

    /** @var list<array{external_reference: string, advertiser_name: string, campaign_name: string, amount_cents: int}> */
    public array $requests = [];

    public function __construct(
        private readonly bool $fail = false,
    ) {
    }

    public function createOpportunity(
        string $externalReference,
        string $advertiserName,
        string $campaignName,
        int $amountCents,
    ): DealOpportunityResult {
        if ($this->fail) {
            throw new DealClientException('Simulated Deal transport failure.');
        }
        if (isset($this->opportunities[$externalReference])) {
            return $this->opportunities[$externalReference];
        }

        $this->requests[] = [
            'external_reference' => $externalReference,
            'advertiser_name' => $advertiserName,
            'campaign_name' => $campaignName,
            'amount_cents' => $amountCents,
        ];

        return $this->opportunities[$externalReference] = new DealOpportunityResult(
            'opp-' . substr(hash('sha256', $externalReference), 0, 16),
            'created',
        );
    }

    public function opportunityCount(): int
    {
        return count($this->opportunities);
    }
}
