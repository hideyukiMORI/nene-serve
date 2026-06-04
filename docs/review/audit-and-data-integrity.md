# Audit & Data Integrity Self-Review

**Binding.** Use for **any** change touching a governed table, a write path,
deletion/lifecycle, the audit log, or retention. If unsure whether data is
governed, **treat it as governed** and run this list.

Source of truth:
[`../explanation/audit-and-data-integrity-compliance.md`](../explanation/audit-and-data-integrity-compliance.md).
Do not delete items to pass. Mark `N/A` only when genuinely not applicable.

## Checklist

- [ ] Change reviewed against `audit-and-data-integrity-compliance.md`; integrity/audit impact stated in the PR.
- [ ] New/changed data correctly classified: **governed** (default) vs **presentation** (explicit allowlist in `terminology.md`).
- [ ] Every governed **mutation** (create/update/transition/soft-delete/override/version-supersede) writes an append-only audit event with actor, before→after, reason.
- [ ] Mutation + audit are **atomic** (same transaction / outbox); no committed mutation can lack its audit record.
- [ ] No governed **hard delete** / `TRUNCATE` in app code; "delete" is `archived`/`disabled` status or an additive tombstone (`*_at`).
- [ ] Foreign keys to tenants/parents on governed tables use **`ON DELETE RESTRICT`** (never `CASCADE`).
- [ ] Sensitive **reads** audited (`include_sensitive`, DSR export, PII-link reads); ordinary reads not.
- [ ] `audit_events` is append-only + **hash-chained**; never cascaded/archived/purged on the analytics schedule.
- [ ] Application DB role has **no `DELETE`/`TRUNCATE`** on governed tables; presentation tables may; destructive ops use the privileged role.
- [ ] Approved creatives / closed billing periods remain immutable (ADR 0015/0020).
- [ ] Privacy erasure is an **additive tombstone** (forget the link, keep the count), itself audited (ADR 0017 §5).
- [ ] Any physical purge gated on **retention expired ∧ no legal hold ∧ not billing-relevant**, and the purge is recorded.
- [ ] New audit `action` names / tombstone fields / presentation-allowlist entries registered in `terminology.md`.
- [ ] Any deviation carries an **ADR with sign-off**.
