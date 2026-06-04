<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Invoice;

/**
 * In-memory Invoice client for boot/tests. **Idempotent on externalReference**: a
 * retry returns the same payment id and does not create a second charge. Can be
 * put into a failing mode to exercise transport failure isolation.
 */
final class FakeInvoiceClient implements InvoiceClientInterface
{
    /** @var array<string, InvoiceChargeResult> keyed by externalReference */
    private array $charges = [];

    /** @var list<array{external_reference: string, invoice_client_id: ?string, amount_cents: int, period_start: string, period_end: string}> */
    public array $requests = [];

    public function __construct(
        private readonly bool $fail = false,
    ) {
    }

    public function postCharge(
        string $externalReference,
        ?string $invoiceClientId,
        int $amountCents,
        string $periodStart,
        string $periodEnd,
    ): InvoiceChargeResult {
        if ($this->fail) {
            throw new InvoiceClientException('Simulated Invoice transport failure.');
        }

        // Idempotent: same external_reference → same result, no second charge.
        if (isset($this->charges[$externalReference])) {
            return $this->charges[$externalReference];
        }

        $this->requests[] = [
            'external_reference' => $externalReference,
            'invoice_client_id' => $invoiceClientId,
            'amount_cents' => $amountCents,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ];

        return $this->charges[$externalReference] = new InvoiceChargeResult(
            'ipay-' . substr(hash('sha256', $externalReference), 0, 16),
            'recorded',
        );
    }

    public function chargeCount(): int
    {
        return count($this->charges);
    }
}
