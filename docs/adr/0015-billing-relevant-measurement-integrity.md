# ADR 0015: Billing-Relevant Measurement Integrity

## Status

accepted

## Context

In marketplace mode (ADR 0014), **counted impressions and clicks become the
basis for advertiser charges**. Measurement data that merely powered charts is
now substantiation for money. An auditor (会計士 / 税理士) reviewing a charge
must be able to trace it back to immutable, reproducible measurement and find no
way for the figures to have been silently changed.

`measurement-spec.md` defines *what counts*. This ADR defines the **integrity
guarantees** required once a count is billing-relevant.

## Decision

1. **Billable definition (single).** A billable event is a counted
   impression/click eligible to incur spend. Non-billable, never accruing spend:
   fallback/default serves, errors/empty serves, bot/invalid-traffic filtered
   events (with reason code), `measurement_enabled=false` placements, and events
   outside an active funded campaign. Reporting and billing use the **same**
   definition and the **same** event records.
2. **Append-only events.** Billing-relevant events are never mutated or deleted
   in place. Privacy erasure is an additive tombstone, not a figure edit.
3. **Billing period & closed-period immutability.** Spend accrues in a
   `billing_period`. Once closed (handed off / reconciled), its billable counts
   and `spent_cents` are **immutable**; corrections are additive adjustments in a
   later period (debit/credit memo via Invoice), never edits to closed figures.
4. **Reproducible pricing.** The unit→money rule (CPM/CPC/flat) is explicit,
   versioned, and stored with the spend snapshot:
   `amount = f(billable_units, pricing_rule_version)`. A non-reproducible figure
   is a defect.
5. **Reconciliation.** Each amount handed to Invoice reconciles to its events:
   `amount_handed = Σ(billable_units) × rate`. A reconciliation run records
   `events → units → amount → external_reference`; discrepancies are surfaced and
   audited, never absorbed.
6. **Idempotent handoff.** Handoff is idempotent on `external_reference`; retries
   never double-charge.
7. **Caps.** `spent_cents` cannot exceed `budget_cents`/caps without audited
   operator action.
8. **Tamper-evident snapshots & retention.** Spend snapshots are versioned, never
   overwritten; billing-relevant records are retained for the money SSOT's
   statutory period (JP: 7y, up to 10y) — no auto-purge before then.
9. **Audit.** Budget/pricing changes, period close, handoff, reconciliation, and
   manual adjustments are auditable (ADR-gated, append-only).

## Consequences

**Benefits**

- Every advertiser charge is traceable to immutable, reproducible measurement.
- Retries, recounts, and corrections cannot silently alter history.

**Costs**

- Billing-relevant data carries a heavier retention and immutability burden than
  ordinary analytics — distinct retention regimes must be implemented (§7 of the
  binding doc; privacy doc P3/P8 for the lighter regime).

## Related

- [`../explanation/billing-and-accounting-compliance.md`](../explanation/billing-and-accounting-compliance.md) (binding)
- [`../explanation/measurement-spec.md`](../explanation/measurement-spec.md)
- [ADR 0014](0014-serve-is-not-the-books-of-account.md), ADR 0012 (imp/click), ADR 0010 (public API security)
- [`../integrations/invoice-advertiser-handoff-contract.md`](../integrations/invoice-advertiser-handoff-contract.md)
