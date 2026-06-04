# Audit & Data Integrity — Binding Rules

**Status: binding (non-negotiable).** Source of truth for how NeNe Serve records
changes and protects governed data from destruction. An auditor or regulator must
be able to find **zero deviations**. Deepens [ADR 0022](../adr/0022-immutability-and-comprehensive-audit.md).

These are **MUST** requirements. Where a rule here conflicts with convenience,
**integrity wins**.

Read first: [ADR 0022](../adr/0022-immutability-and-comprehensive-audit.md),
[ADR 0006](../adr/0006-multi-tenancy-and-roles.md),
[`billing-and-accounting-compliance.md`](./billing-and-accounting-compliance.md),
[`privacy-and-ad-compliance.md`](./privacy-and-ad-compliance.md),
self-review [`../review/audit-and-data-integrity.md`](../review/audit-and-data-integrity.md).

---

## 0. Governing principles

1. **Attributable & reconstructable.** Every governed change records who, when,
   what (before → after), and why.
2. **No silent or manual destruction.** Governed data is never hard-deleted by the
   app or by hand; removal is governed, policy-driven, and audited.
3. **Enforced, not promised.** Integrity is guaranteed at the database layer, not
   only by application discipline.
4. **Reconcile, never mutate.** Privacy erasure and retention purges are explicit,
   audited processes that never silently edit counts or figures.
5. **No silent deviation.** Any departure requires an ADR with sign-off.

---

## 1. Two data classes

| Class | Examples | Editable? | Hard delete? | Audited? |
| --- | --- | --- | --- | --- |
| **Governed** | placements, creatives, campaigns, delivery plans, users/roles, advertisers/budgets, impressions/clicks/serve requests, consent records, audit events | versioned / state-change only | **No** (tombstone/archive) | **All writes** + sensitive reads |
| **Presentation** | UI theme, dashboard layout, column order, saved filters, a user's display-locale preference | yes | yes | no |

The **presentation allowlist** is explicit (`terminology.md` → Presentation data).
Anything not on it is **governed** by default. Adding to the allowlist requires a
PR that states why the data has no delivery/measurement/billing/identity meaning.

---

## 2. Mutations (writes)

- Every governed create / update / state transition / soft-delete / override /
  version-supersede writes an **append-only audit event**: `organization_id`,
  `actor_user_id` (or service token id), `action`, `subject_type`, `subject_id`,
  **`before`/`after`**, `reason`, `occurred_at`.
- The audit write and the mutation are **atomic** (same transaction, or a
  transactional outbox): a committed mutation without its audit record is
  impossible.
- A governed mutation with **no** audit event is a **defect**, not a style nit.

---

## 3. Deletion & lifecycle

- **No `DELETE` / `TRUNCATE`** on governed tables from the application role
  (enforced by DB grants, §6). "Delete" is `status = archived|disabled` or an
  additive tombstone column (`archived_at` / `disabled_at` / `erased_at`).
- **No `ON DELETE CASCADE`** from governed tables to tenants/parents — use
  `RESTRICT`. A tenant or parent cannot be physically removed together with its
  audit, billing, or measurement trail.
- **Approved creatives** and **closed billing periods** are immutable
  (ADR 0015/0020); edits create new versions / later-period adjustments.

---

## 4. Reads (sensitive only)

Audit a **read** only when it exposes sensitive data:

- metrics with `include_sensitive=true`,
- DSR **export** of a visitor's data,
- any read that resolves a PII link (e.g. visitor-level lookup).

Ordinary aggregate/admin reads are **not** audited (cost vs value).

---

## 5. Tamper-evident audit log

- `audit_events` is append-only and **hash-chained**: each row stores a hash over
  its own fields plus the previous row's hash (per tenant), so deletion or
  edit of any prior row is detectable.
- The audit log is **never** cascaded, archived away, or purged on the analytics
  schedule. It follows the **longest** applicable retention (billing-relevant
  spans use the statutory window, billing doc §7).
- The audit log cannot audit itself; its integrity is the hash chain plus the
  DB-level no-delete grant.

---

## 6. Database-level enforcement

- The application DB role has `SELECT, INSERT, UPDATE` on governed tables and
  **no `DELETE`, no `TRUNCATE`, no `DROP`**.
- Presentation tables may additionally grant `DELETE`.
- Schema migrations use `ON DELETE RESTRICT` for tenant/parent foreign keys on
  governed tables.
- Destructive maintenance (test resets, governed purges §7) uses a **separate
  privileged role**, never the application role, and is logged out-of-band.

---

## 7. Governed removal (retention, legal hold, erasure)

Physical removal of a governed row happens **only** when **all** hold:

1. its **retention window has expired** (privacy §6 for ordinary measurement;
   statutory for billing-relevant, billing doc §7), **and**
2. **no legal hold** applies, **and**
3. it is **not** billing-relevant within its statutory window.

The purge is performed by the privileged role through a documented job and is
itself recorded (count, scope, time, operator). **Privacy erasure** (DSR) does not
remove the factual row: it sets `erased_at` and nulls the pseudonymous link
(`visitor_bucket`), preserving the count (ADR 0017 §5) — and that erasure is
audited.

---

## 8. How this applies to every change

Any change touching a governed table, a write path, deletion/lifecycle, audit, or
retention **MUST**:

1. Be reviewed against this document and
   [`../review/audit-and-data-integrity.md`](../review/audit-and-data-integrity.md).
2. State its integrity/audit impact in the PR.
3. If it deviates, carry an ADR with sign-off.

If unsure whether data is governed, **treat it as governed**.

---

## Related

- [ADR 0022](../adr/0022-immutability-and-comprehensive-audit.md), [ADR 0006](../adr/0006-multi-tenancy-and-roles.md), [ADR 0015](../adr/0015-billing-relevant-measurement-integrity.md), [ADR 0017](../adr/0017-consent-and-lawful-basis.md), [ADR 0020](../adr/0020-creative-review-workflow.md)
- [`billing-and-accounting-compliance.md`](./billing-and-accounting-compliance.md), [`privacy-and-ad-compliance.md`](./privacy-and-ad-compliance.md)
- [`../review/audit-and-data-integrity.md`](../review/audit-and-data-integrity.md)

Last updated: 2026-06-04
