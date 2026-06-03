# Domain Model (Phase 0)

## Tenant

- **Organization**, **User**, roles (ADR 0006)

## Serving core

- **Placement** — `public_placement_key`, `allowed_origins`, locale defaults
- **Creative** — type, asset URLs or bundle id, `destination_url`, review status
- **Campaign** — groups creatives; schedule; status
- **DeliveryPlan** — weights, caps, `default_creative_id`
- **Impression**, **Click** — append-only events (partition-friendly)

## Marketplace (Phase 3+)

Money SSOT = NeNe Invoice (ADR 0014). Serve holds **net counters and
substantiation only** — no ledger, no tax, no payment-of-record.

- **Advertiser**, **Budget** (`budget_cents`)
- **PricingRule** (`pricing_model`: `cpm`/`cpc`/`flat`, `pricing_rule_version`)
- **BillingPeriod** (`status`: `open`/`closed`/`reconciled`/`handed_off`)
- **SpendSnapshot** — versioned, tamper-evident; backs a handoff (`spent_cents`)
- **ReconciliationRun** — links billable events → units → amount → `external_reference`
- Invoice linkage: `external_reference`, `invoice_client_id`, `invoice_payment_id`

Billing-relevant events and spend snapshots are **append-only** and immutable
once a period is closed (ADR 0015), retained for the money SSOT's statutory
period (see [`billing-and-accounting-compliance.md`](./billing-and-accounting-compliance.md) §7).

## Audit

- **AuditEvent** — plan changes, creative publish, MCP writes, budget/pricing
  changes, billing-period close, handoff, reconciliation, manual adjustments

## Forbidden tables (belong to siblings, not Serve)

No `submission`, `invoice`, `bank_transaction`, `scenario`, or any **tax / ledger
/ payment-of-record** tables in the Serve DB (ADR 0009, ADR 0014).

Last updated: 2026-06-04
