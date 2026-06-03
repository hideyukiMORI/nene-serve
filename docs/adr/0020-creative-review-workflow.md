# ADR 0020: Creative Review Workflow & Approval Gating

## Status

accepted

## Context

ADR 0013 established that creatives — especially HTML5 bundles — must be reviewed
before serving, and that approve/reject is required before `active` status. It did
not define the **workflow**, **who may approve**, or **what happens on change**.
Without that, "reviewed" is unenforceable and an operator could serve an
unreviewed or silently-modified asset.

## Decision

1. **Review state machine:**
   `draft → submitted → in_review → approved | rejected | changes_requested`.
   Only `draft` / `changes_requested` are editable; `submitted` onward locks the
   asset and `destination_url`.
2. **Serving gate:** only a creative with `review_status = approved` in an
   `active` (and, in marketplace mode, `funded`) campaign is eligible to serve.
   Non-eligible creatives are never served and never billable.
3. **Approval authority:** transitioning to `approved` requires the
   `review_creatives` capability (ADR 0006).
4. **Four-eyes by default:** the submitter may not approve their own creative; an
   exception requires an **audited override**.
5. **Immutability + re-review:** an approved creative is immutable. Editing
   produces a **new `creative_version`** at `draft` that must be re-reviewed; the
   prior version is retained as immutable history.
6. **Auditing:** submit/approve/reject/changes_requested/override/version-supersede
   are append-only audited events (who/when/from→to/reason).
7. **Deviation gate:** changing the workflow or the approval authority requires an
   ADR with security sign-off.

## Consequences

**Benefits**

- "Approved" is enforceable and traceable; no serving of unreviewed or silently
  edited assets.
- Clear, auditable accountability for what went live.

**Costs**

- Requires versioning + a review queue UI (Phase 2); single-operator orgs need a
  documented self-approval override path.

## Related

- [`../explanation/creative-review-and-safety.md`](../explanation/creative-review-and-safety.md) (binding)
- [ADR 0021](0021-creative-acceptance-and-sandbox-safety.md), ADR 0013, ADR 0006, ADR 0012
