# ADR 0022: Immutability & Comprehensive Audit

## Status

accepted

## Context

NeNe Serve holds legally sensitive data: ad-delivery decisions, billing-relevant
impression/click counts (ADR 0014/0015), creative review trails (ADR 0020), and
consent records (ADR 0017). For this to be **defensible** — to an accountant, an
auditor, or a regulator — two properties must hold for governed data:

1. Every change is **attributable and reconstructable** (who/when/before→after/why).
2. Records are **not silently or manually destroyed**.

ADR 0006 already mandates audit events; ADR 0015 fixes billing-figure immutability;
ADR 0020 §6 audits review decisions; ADR 0017 §5 makes erasure an additive
tombstone. This ADR **generalizes** those into one binding model and makes it
**enforced**, not merely policy.

The tension to resolve: a blanket "never delete" would violate the privacy
**erasure right** (GDPR Art. 17 / APPI) and **data-minimization / shortest-viable
retention** (privacy §6, P3). The resolution is that removal is **never ad-hoc**;
it happens only through a governed, audited process, and PII erasure is satisfied
by tombstoning the **link to the person**, not by destroying the factual record.

## Decision

1. **Two data classes.**
   - **Governed data** — everything with delivery, measurement, billing, review,
     consent, identity, or configuration meaning.
   - **Presentation data** — cosmetic UI state only, by an **explicit allowlist**
     (UI theme, dashboard layout, column order, saved filters, a user's display
     locale preference). Presentation data is freely editable/deletable and **not**
     audited.

2. **Append-only / no ad-hoc physical delete (governed data).** Governed data is
   never hard-deleted or `TRUNCATE`d by the application or by hand. "Delete" is a
   **state change** (`archived` / `disabled`) or an **additive tombstone**
   (`archived_at` / `disabled_at` / `erased_at`), never a row removal or a figure
   edit. Approved creatives and closed billing periods stay immutable
   (ADR 0015/0020).

3. **Comprehensive write audit.** Every governed **mutation** (create, update,
   state transition, soft-delete, override, version supersede) writes an
   append-only audit event: `organization_id`, actor, `action`, subject, **before
   → after**, reason, timestamp. A governed mutation without an audit record is a
   defect.

4. **Sensitive-read audit.** Reads are audited only when they expose sensitive
   data: `include_sensitive` metrics, DSR export, and any read of a PII link.
   Ordinary reads are not audited.

5. **Tamper-evident audit log.** `audit_events` is append-only and
   **hash-chained** (each row includes the prior row's hash) so gaps or edits are
   detectable. The audit log itself is never cascaded away or deleted (see §7).

6. **Enforced at the database, not just the app.** The application DB role has
   **no `DELETE`/`TRUNCATE`** on governed tables; foreign keys to tenants use
   `RESTRICT` (never `ON DELETE CASCADE`) so a tenant cannot be physically removed
   together with its audit/billing trail. Presentation tables may grant `DELETE`.

7. **Governed removal is policy-driven and audited.** Physical removal happens
   **only** when retention has expired **and** no legal hold applies **and** the
   record is not billing-relevant within its statutory window (ADR 0015,
   privacy §6); the purge itself is audited. Privacy erasure is an additive
   tombstone that forgets the visitor link while preserving the count (ADR 0017 §5).

8. **Deviation gate.** Weakening immutability, audit coverage, the cosmetic
   allowlist, or the DB-level enforcement requires an ADR with sign-off.

## Consequences

**Benefits**

- A complete, attributable, tamper-evident history of everything that mattered;
  no governed record can vanish by bug, mistake, or manual `DELETE`.
- Accounting (ADR 0015) and privacy (ADR 0017) obligations are reconciled
  explicitly rather than by silent mutation.

**Costs**

- Storage grows; retention/partitioning and a governed purge process are required
  (not optional).
- Every write path carries an audit obligation; the cosmetic allowlist must be
  maintained deliberately.
- Hard deletes are impossible at the DB layer, so test/dev resets use a separate
  privileged path, not the app role.

## Related

- [`../explanation/audit-and-data-integrity-compliance.md`](../explanation/audit-and-data-integrity-compliance.md) (binding)
- [ADR 0006](0006-multi-tenancy-and-roles.md), [ADR 0015](0015-billing-relevant-measurement-integrity.md), [ADR 0017](0017-consent-and-lawful-basis.md), [ADR 0020](0020-creative-review-workflow.md)
- [`../review/audit-and-data-integrity.md`](../review/audit-and-data-integrity.md)
