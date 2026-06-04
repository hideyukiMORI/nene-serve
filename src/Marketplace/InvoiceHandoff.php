<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

/**
 * The reconciliation + handoff record for a closed billing period (billing
 * §3.4/§3.5). Captures the linkage events → billable_units → amount →
 * external_reference, the reconciliation outcome, and the Invoice result.
 * Idempotent on `externalReference` — one logical handoff per period/snapshot.
 * Amounts/units are immutable; only `status`/`invoicePaymentId` fill in.
 */
final class InvoiceHandoff
{
    public function __construct(
        public readonly string $id,
        public readonly string $organizationId,
        public readonly string $billingPeriodId,
        public readonly string $externalReference,
        public readonly int $billableImpressions,
        public readonly int $billableClicks,
        public readonly int $pricingRuleVersion,
        public readonly int $amountCents,
        public readonly string $reconciliationStatus,
        public readonly string $status,
        public readonly ?string $invoicePaymentId,
        public readonly string $createdAt,
    ) {
    }

    public function withResult(string $status, ?string $invoicePaymentId): self
    {
        return new self(
            $this->id,
            $this->organizationId,
            $this->billingPeriodId,
            $this->externalReference,
            $this->billableImpressions,
            $this->billableClicks,
            $this->pricingRuleVersion,
            $this->amountCents,
            $this->reconciliationStatus,
            $status,
            $invoicePaymentId,
            $this->createdAt,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'billing_period_id' => $this->billingPeriodId,
            'external_reference' => $this->externalReference,
            'billable_impressions' => $this->billableImpressions,
            'billable_clicks' => $this->billableClicks,
            'pricing_rule_version' => $this->pricingRuleVersion,
            'amount_cents' => $this->amountCents,
            'currency' => 'JPY',
            'reconciliation_status' => $this->reconciliationStatus,
            'status' => $this->status,
            'invoice_payment_id' => $this->invoicePaymentId,
        ];
    }
}
