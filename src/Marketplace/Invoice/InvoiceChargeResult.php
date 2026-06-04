<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Invoice;

/** Result of posting a net charge to NeNe Invoice (money SSOT, ADR 0014). */
final class InvoiceChargeResult
{
    public function __construct(
        public readonly string $invoicePaymentId,
        public readonly string $status,
    ) {
    }
}
