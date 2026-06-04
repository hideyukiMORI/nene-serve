<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

use PDO;

final class PdoInvoiceHandoffRepository implements InvoiceHandoffRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, billing_period_id, external_reference, billable_impressions, billable_clicks, pricing_rule_version, amount_cents, reconciliation_status, status, invoice_payment_id, created_at';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function findByExternalReference(string $organizationId, string $externalReference): ?InvoiceHandoff
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM invoice_handoffs WHERE organization_id = ? AND external_reference = ? LIMIT 1');
        $stmt->execute([$organizationId, $externalReference]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    public function save(InvoiceHandoff $handoff): void
    {
        // REPLACE keyed by the unique external_reference: amounts/units are stable;
        // only status / invoice_payment_id advance (handoff idempotency).
        $stmt = $this->pdo->prepare(
            'INSERT INTO invoice_handoffs (id, organization_id, billing_period_id, external_reference, billable_impressions, billable_clicks, pricing_rule_version, amount_cents, reconciliation_status, status, invoice_payment_id, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) AS new
             ON DUPLICATE KEY UPDATE billing_period_id = new.billing_period_id, external_reference = new.external_reference, billable_impressions = new.billable_impressions, billable_clicks = new.billable_clicks, pricing_rule_version = new.pricing_rule_version, amount_cents = new.amount_cents, reconciliation_status = new.reconciliation_status, status = new.status, invoice_payment_id = new.invoice_payment_id',
        );
        $stmt->execute([
            $handoff->id,
            $handoff->organizationId,
            $handoff->billingPeriodId,
            $handoff->externalReference,
            $handoff->billableImpressions,
            $handoff->billableClicks,
            $handoff->pricingRuleVersion,
            $handoff->amountCents,
            $handoff->reconciliationStatus,
            $handoff->status,
            $handoff->invoicePaymentId,
            $handoff->createdAt,
        ]);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): InvoiceHandoff
    {
        return new InvoiceHandoff(
            (string) $row['id'],
            (string) $row['organization_id'],
            (string) $row['billing_period_id'],
            (string) $row['external_reference'],
            (int) $row['billable_impressions'],
            (int) $row['billable_clicks'],
            (int) $row['pricing_rule_version'],
            (int) $row['amount_cents'],
            (string) $row['reconciliation_status'],
            (string) $row['status'],
            $row['invoice_payment_id'] !== null ? (string) $row['invoice_payment_id'] : null,
            (string) $row['created_at'],
        );
    }
}
