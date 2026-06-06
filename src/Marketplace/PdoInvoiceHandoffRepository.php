<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeneServe\Support\SqlDialect;

final readonly class PdoInvoiceHandoffRepository implements InvoiceHandoffRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, billing_period_id, external_reference, billable_impressions, billable_clicks, pricing_rule_version, amount_cents, reconciliation_status, status, invoice_payment_id, created_at';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private SqlDialect $dialect = SqlDialect::Mysql,
    ) {
    }

    public function findByExternalReference(string $organizationId, string $externalReference): ?InvoiceHandoff
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::COLUMNS . ' FROM invoice_handoffs WHERE organization_id = ? AND external_reference = ? LIMIT 1',
            [$organizationId, $externalReference],
        );

        return $row === null ? null : $this->hydrate($row);
    }

    public function save(InvoiceHandoff $handoff): void
    {
        // Upsert keyed by the unique external_reference: amounts/units are stable;
        // only status / invoice_payment_id advance (handoff idempotency).
        $this->query->execute(
            $this->dialect->upsert(
                'invoice_handoffs',
                ['id', 'organization_id', 'billing_period_id', 'external_reference', 'billable_impressions', 'billable_clicks', 'pricing_rule_version', 'amount_cents', 'reconciliation_status', 'status', 'invoice_payment_id', 'created_at'],
                ['organization_id', 'external_reference'],
                ['billing_period_id', 'billable_impressions', 'billable_clicks', 'pricing_rule_version', 'amount_cents', 'reconciliation_status', 'status', 'invoice_payment_id'],
            ),
            [
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
            ],
        );
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
