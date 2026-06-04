<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

/**
 * A marketplace advertiser (domain-model Phase 3+). Serve holds operational
 * identity only; the advertiser's money system of record is NeNe Invoice
 * (ADR 0014). `invoiceClientId` links to the Invoice-side client after handoff.
 */
final class Advertiser
{
    public function __construct(
        public readonly string $id,
        public readonly string $organizationId,
        public readonly string $name,
        public readonly string $status = 'active',
        public readonly ?string $invoiceClientId = null,
        public readonly ?string $disabledAt = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'invoice_client_id' => $this->invoiceClientId,
        ];
    }
}
