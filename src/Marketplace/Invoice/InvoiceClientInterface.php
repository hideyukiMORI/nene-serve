<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Invoice;

/**
 * Hands a **net, tax-free** charge to NeNe Invoice (handoff contract, ADR 0014).
 * Serve never sends a tax figure or rate; Invoice applies tax and issues the
 * qualified invoice. Idempotent on `externalReference` — a retry must not create
 * a double charge.
 */
interface InvoiceClientInterface
{
    /**
     * @param int $amountCents net amount, JPY minimum units (no tax)
     *
     * @throws InvoiceClientException on transport failure (retryable)
     */
    public function postCharge(
        string $externalReference,
        ?string $invoiceClientId,
        int $amountCents,
        string $periodStart,
        string $periodEnd,
    ): InvoiceChargeResult;
}
