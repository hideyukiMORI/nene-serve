# Invoice Advertiser Handoff Contract

**Status: draft contract — binding once Invoice exposes advertiser charge
endpoints.** Until then it governs design; on activation its rules become
non-negotiable under
[`../explanation/billing-and-accounting-compliance.md`](../explanation/billing-and-accounting-compliance.md).

## Purpose

In **marketplace mode** advertiser delivery is tracked in Serve (`budget_cents`,
`spent_cents`, billable counts) but **money SSOT is NeNe Invoice** (ADR 0014).
Serve hands a **net, tax-free taxable base** to Invoice; Invoice applies tax and
issues qualified invoices. HTTP only — ADR 0002.

## Serve stores

- `advertiser_id`, `budget_cents`, `spent_cents` (derived from billable delivery)
- `pricing_rule_version` (the CPM/CPC/flat rule used — reproducibility)
- `billing_period` boundaries and per-period **spend snapshot** (versioned)
- `invoice_client_id`, `invoice_payment_id`, `external_reference` after handoff

## Serve calls Invoice

- Create or sync the advertiser as an Invoice **client** (draft)
- Post the **net** charge / line base for the billing period (no tax, no tax
  classification — Invoice classifies and computes)
- Poll payment status to drive `pause_on_budget_exhausted`

## Integrity obligations (binding on activation)

- **Idempotency.** Every handoff carries an `external_reference`; Invoice and
  Serve treat it idempotently — a retry **MUST NOT** create a double charge.
- **Reconciliation.** The handed-off amount **MUST** reconcile to underlying
  billable events: `amount = Σ(billable_units) × rate (pricing_rule_version)`. A
  reconciliation run records `events → units → amount → external_reference`;
  discrepancies are surfaced and audited, never absorbed.
- **Net only.** Amounts are integer `*_cents`, **net of tax**. Serve never sends
  a tax figure or rate.
- **Closed-period immutability.** Once a period is handed off, its figures are
  immutable; corrections are additive adjustments in a later period (debit/credit
  memo handled by Invoice).
- **Audit.** Handoff, reconciliation, and budget/pricing changes are audited.
- **Failure isolation.** A failed Invoice handoff does **not** pause serving
  unless the operator enables `pause_on_budget_exhausted`.

## Serve must NOT

- Compute, round, classify, or display consumption tax (or any tax)
- Issue qualified invoices, credit notes, or receipts
- Store card / bank account numbers, or process payments
- Treat `spent_cents` as authoritative revenue / accounts receivable
- Hand off figures that already include tax

## Environment variables

| Variable | Purpose |
| --- | --- |
| `NENE_INVOICE_API_BASE_URL` | Invoice `/api/*` base |
| `NENE_INVOICE_SERVICE_TOKEN` | Scoped machine token |

## Related

- [`../explanation/billing-and-accounting-compliance.md`](../explanation/billing-and-accounting-compliance.md) (binding)
- ADR 0002, [ADR 0014](../adr/0014-serve-is-not-the-books-of-account.md), [ADR 0015](../adr/0015-billing-relevant-measurement-integrity.md)
- [`sibling-products.md`](./sibling-products.md)

Last updated: 2026-06-04
