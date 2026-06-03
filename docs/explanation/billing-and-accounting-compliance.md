# Billing & Accounting Compliance — Binding Rules

**Status: binding (non-negotiable).** This document is the source of truth for
how NeNe Serve behaves around **money, billing, and accounting/tax records**. A
finance, accounting, or tax professional (会計士 / 税理士) reviewing the system
must be able to find **zero deviations** from the rules below.

These are not guidelines. They are **MUST** requirements. Where a rule here
conflicts with UX, performance, implementation convenience, or any other
concern, **compliance wins** — every time, without exception.

Read first: [`scope-contract.md`](./scope-contract.md),
[`measurement-spec.md`](./measurement-spec.md),
[ADR 0014](../adr/0014-serve-is-not-the-books-of-account.md),
[ADR 0015](../adr/0015-billing-relevant-measurement-integrity.md),
handoff contract
[`../integrations/invoice-advertiser-handoff-contract.md`](../integrations/invoice-advertiser-handoff-contract.md),
self-review checklist [`../review/billing-compliance.md`](../review/billing-compliance.md).

---

## 0. Governing principles

1. **Serve is not the books of account (会計帳簿ではない).** Serve holds
   measurement events and operational money **counters** (e.g. `budget_cents`,
   `spent_cents`). It does **not** hold journal entries, ledgers, accounts
   receivable, or financial statements. The accounting and tax **system of
   record** for advertiser money is **NeNe Invoice** (the *money SSOT*).
2. **Serve makes no tax determination (税ニュートラル).** Serve **never**
   computes, rounds, classifies, or displays consumption tax (消費税) or any
   other tax; it **never** issues a qualified invoice (適格請求書); it **never**
   records a payment as authoritative. Jurisdictional tax law is applied by the
   money SSOT — see §2.
3. **Compliance is non-negotiable.** Correct adherence to these rules takes
   precedence over every other product goal.
4. **No silent deviation.** Any departure from this document — even temporary —
   requires an **ADR** and **explicit review sign-off by a tax/accounting
   professional (税理士 / 会計士)** recorded in that ADR. Code may not merge a
   deviation without it.
5. **Engineering is not the legal authority.** This document is engineering's
   binding interpretation. When a requirement is unclear, **stop and consult a
   professional** — do not guess. Record the resolved interpretation here.
6. **Single source of truth for every billing-relevant figure.** Each
   billing-relevant figure is derived **once**, in the measurement pipeline /
   UseCase, from append-only event data. The handoff payload, API response, CSV
   export, and stored snapshot render the **exact same** numbers; no layer
   recalculates independently.

---

## 1. Where money exists in Serve (and where it does not)

| Mode | Money in Serve? | Notes |
| --- | --- | --- |
| **Self-serve publisher** (Phase 1–2) | **No** | The publisher sells ad space off-platform. Serve only delivers and measures. No money flows through Serve. |
| **Marketplace** (Phase 3+) | **Counters only** | Advertisers fund campaigns. Serve tracks `budget_cents` / `spent_cents` and hands a taxable base to Invoice. Money SSOT stays in Invoice. |

There is **no** other path by which money enters Serve. Serve has no checkout,
no card vault, no payout engine, no ledger.

---

## 2. Statutory posture — tax-neutral, money SSOT applies the law

NeNe Serve is **jurisdiction-neutral for money**. It does not encode any
country's tax rules. The money SSOT (NeNe Invoice, Japan-targeted) applies the
applicable rules to the taxable base Serve hands off:

| Area | Applied by | Serve's obligation |
| --- | --- | --- |
| Consumption tax (消費税法, 10% / 8% 軽減税率) | **Invoice** | Hand off **net** taxable amounts only; never include or imply tax |
| Qualified invoice (適格請求書等保存方式 / インボイス制度) | **Invoice** | Provide advertiser identity + period + base so Invoice can issue; never issue documents itself |
| Electronic records retention (電子帳簿保存法) | **Invoice** (for issued documents) | Retain Serve's **billing-relevant substantiation** (events, spend snapshots) tamper-evidently — see §7 |

Serve's job at the boundary is to give the money SSOT a **clean, complete,
auditable, tax-free taxable base**, so the SSOT can apply the law correctly. See
[`../integrations/invoice-advertiser-handoff-contract.md`](../integrations/invoice-advertiser-handoff-contract.md).

If a tax rate, statutory field, or retention rule changes, that is **Invoice's**
defect to fix; Serve only changes if the *handoff shape* must change, which is an
ADR-level event.

---

## 3. Billing-relevant measurement integrity

When advertisers pay based on delivery, **counted impressions and clicks become
billing-relevant figures**. From that moment they are held to an audit-grade
standard. Governed in detail by
[ADR 0015](../adr/0015-billing-relevant-measurement-integrity.md).

### 3.1 What is billable

A **billable** event is a counted impression/click (per
[`measurement-spec.md`](./measurement-spec.md)) that is eligible to incur
advertiser spend. The following are **non-billable** and **MUST NOT** accrue
spend:

- Fallback / default-creative serves (no funded advertiser behind them)
- API errors and empty serves
- Bot / invalid-traffic filtered events — recorded with a **reason code**, never
  dropped silently
- Placements with `measurement_enabled=false` (opt-out)
- Events outside an **active, funded** campaign window

The billable/non-billable boundary is **defined once** and reused by reporting,
spend accrual, and handoff. Reporting fill-rate and billable counts are derived
from the **same** event records.

### 3.2 Derivation and immutability

- Billable figures are derived from **append-only** event logs. Historical
  events are **never mutated or deleted** (subject to §7 retention and privacy
  erasure handled as additive tombstones, not figure edits).
- Spend accrues within a defined **`billing_period`** per advertiser/campaign.
- **Once a billing period is closed** (handed off and/or reconciled), its
  billable counts and derived `spent_cents` are **immutable**. Corrections are
  made by an **explicit, audited adjustment** recorded in a later period (a
  debit/credit memo handled by Invoice), **never** by editing closed-period
  figures.

### 3.3 Pricing transparency (reproducibility)

The rule that converts billable units into money (e.g. CPM / CPC / flat) is
**explicit, versioned, and stored with the spend snapshot**, so a reviewer can
**reproduce** every figure: `amount = f(billable_units, pricing_rule_version)`.
A figure that cannot be reproduced from stored inputs is a compliance defect.

### 3.4 Reconciliation

- Every amount handed to Invoice **MUST reconcile** to its underlying billable
  events: `amount_handed = Σ(billable_units) × rate` (or the documented budget
  consumption rule).
- A **reconciliation run** records the linkage
  `events → billable_units → amount → external_reference`. Any discrepancy is
  **surfaced** (and audited), **never silently absorbed**.

### 3.5 Idempotency and caps

- Handoff to Invoice is **idempotent on `external_reference`** — a retry **MUST
  NOT** create a double charge.
- `spent_cents` **MUST NOT** exceed `budget_cents` or configured caps without an
  explicit, audited operator action. `pause_on_budget_exhausted` defines whether
  delivery stops automatically.

---

## 4. Money representation

- All amounts are stored and transmitted as **integer minimum currency units**
  (`*_cents`; for JPY, ¥1 = 1 unit). **Float and DECIMAL for money are
  prohibited** in DB, API JSON, and tests.
- Serve stores **net** amounts only — **no tax component, ever** (§2).
- Phase 3 currency is **JPY only**; adding a currency is an ADR-level change.

---

## 5. What Serve must NEVER do (accounting / tax)

| # | Serve must NOT | Belongs to |
| --- | --- | --- |
| B1 | Compute, round, classify, or display **consumption tax** or any tax | Invoice |
| B2 | Issue **qualified invoices**, credit notes, or receipts as SSOT | Invoice |
| B3 | Store **card / bank account numbers** or process payments | Payment provider via Invoice |
| B4 | Act as a **general ledger / journal** or produce financial statements | Accounting software |
| B5 | Treat its spend counters as authoritative **revenue / accounts receivable** | Invoice |
| B6 | **Mutate closed-period** billable figures, or delete billing-relevant events/snapshots before statutory retention | — |
| B7 | **Auto-increase spend** past `budget_cents` / caps without audited operator action | Operator + audit |
| B8 | Hand off figures that **already include tax** | Net base only (§2) |
| B9 | Present a registration-number / tax-status field as **validated or proof of anything** | Invoice validates at issuance |

These mirror and extend `scope-contract.md` rows **X3, X10**.

---

## 6. Audit trail

The following are **auditable events** (who / when / what), tamper-evident and
append-only:

- Budget create / change (`budget_cents`)
- Pricing rule create / change (and version)
- Campaign funding status change
- **Billing period close**
- **Handoff** to Invoice (with `external_reference`)
- **Reconciliation** result (including any discrepancy)
- **Manual adjustment** to spend or billable figures
- Any access to sensitive metrics (`include_sensitive=true`)

Audit records follow the same no-silent-mutation rule as §7.

---

## 7. Retention of billing-relevant records

- **Billing-relevant** events and **spend snapshots** (the substantiation behind
  a charge) are retained for the **statutory period applicable to the money
  SSOT** — for Japan, **7 years**, extendable to **10 years** in certain
  loss-carryforward situations. Serve **MUST NOT** auto-purge billing-relevant
  data before that period. Operators are warned before any destructive retention
  action.
- Retained snapshots are **tamper-evident**: a stored snapshot is **never**
  silently mutated; re-deriving produces a **new versioned record**, not an
  in-place overwrite.
- **Non-billing** measurement data (analytics with no advertiser money behind
  it) follows the **data-minimizing** retention policy in
  [`privacy-and-ad-compliance.md`](./privacy-and-ad-compliance.md) (P3, P8) —
  shorter, configurable, privacy-first. **Do not conflate the two retention
  regimes.**

---

## 8. How this rule applies to every change

Any change that touches advertiser budgets, spend accrual, billable definitions,
billing periods, the Invoice handoff, reconciliation, money representation, or
retention **MUST**:

1. Be reviewed against this document and
   [`../review/billing-compliance.md`](../review/billing-compliance.md).
2. State its compliance impact in the PR.
3. If it deviates from any rule here, carry an **ADR with professional sign-off**
   (§0.4). No exceptions.

If you are unsure whether a change has billing/accounting impact, **assume it
does** and run the checklist.

---

## Related

- [`scope-contract.md`](./scope-contract.md) — GOAL / DO / DON'T
- [`measurement-spec.md`](./measurement-spec.md) — impression / click definitions
- [`privacy-and-ad-compliance.md`](./privacy-and-ad-compliance.md) — privacy retention
- [ADR 0014](../adr/0014-serve-is-not-the-books-of-account.md),
  [ADR 0015](../adr/0015-billing-relevant-measurement-integrity.md)
- [`../integrations/invoice-advertiser-handoff-contract.md`](../integrations/invoice-advertiser-handoff-contract.md)

Last updated: 2026-06-04
