# Billing & Accounting Compliance Self-Review

**Binding.** Use for **any** change touching advertiser budgets, spend accrual,
billable definitions, billing periods, the Invoice handoff, reconciliation, money
representation, or retention. If unsure whether a change has billing/accounting
impact, **assume it does** and run this list.

Source of truth:
[`../explanation/billing-and-accounting-compliance.md`](../explanation/billing-and-accounting-compliance.md).
Do not delete items to pass. Mark `N/A` only when genuinely not applicable.

## Checklist

- [ ] Change reviewed against `billing-and-accounting-compliance.md`; compliance impact stated in the PR.
- [ ] Serve computes/rounds/displays **no tax** of any kind; no qualified invoice/credit note/receipt issued by Serve.
- [ ] Handoff to Invoice carries **net** amounts only — **no tax component**, no tax classification.
- [ ] Money SSOT remains Invoice; Serve counters (`budget_cents`/`spent_cents`) are not treated as authoritative revenue/AR.
- [ ] Billable vs non-billable boundary matches the single definition (fallback/error/bot/opt-out/unfunded excluded); reporting and billing use the same event records.
- [ ] Billing-relevant events are **append-only**; no in-place mutation or hard delete.
- [ ] Closed billing periods are **immutable**; corrections are additive adjustments in a later period, not edits to closed figures.
- [ ] Every billing-relevant figure is **reproducible** from stored inputs (`billable_units` × versioned pricing rule).
- [ ] Amount handed to Invoice **reconciles** to underlying billable events; discrepancies surfaced and audited, never absorbed.
- [ ] Handoff is **idempotent on `external_reference`**; retries cannot double-charge.
- [ ] `spent_cents` cannot exceed `budget_cents`/caps without audited operator action.
- [ ] All money is **integer minimum currency units**; no float/DECIMAL in DB, JSON, or tests; JPY only (Phase 3).
- [ ] Spend snapshots are **tamper-evident** (versioned, never overwritten).
- [ ] Billing-relevant data retained for the statutory period (JP: 7y, up to 10y); **no auto-purge** before then; distinct from the privacy retention regime.
- [ ] Audit trail recorded for budget/pricing changes, period close, handoff, reconciliation, and manual adjustments.
- [ ] No card/bank account numbers stored; no payment processed by Serve.
- [ ] Any deviation from the binding rules carries an **ADR with tax/accounting professional sign-off**.
