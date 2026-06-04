# Current TODO

**Phase 0 — Governance** ✅ complete on `main` (2026-06-03)

## Phase 0 checklist

- [x] Public repo scaffold + six locale placeholders
- [x] Scope contract, measurement spec, serve.js spec, privacy/ad compliance
- [x] ADRs 0001–0013 (incl. six-locale, Contact/Concierge split, creative sandbox)
- [x] Sibling map (Invoice handoff draft; no Clear/Profile paths)
- [x] GitHub Issue #1 closed

## Next (Phase 1 — milestone "Phase 1: Foundation")

- [x] #10 (A) Runtime scaffold — NENE2 layout, `GET /health`, `composer locales:check` stub, Docker stack (8910/8911/3392)
- [x] #11 (B) Multi-tenant org/auth model — roles/capabilities, JWT bearer auth, tenant isolation (ADR 0006/0018)
- [x] #12 (C) Three API surfaces + serve API baseline — origin gating, rate limits, short-lived single-use click tokens, no open redirect, idempotent impression, scoped service tokens (ADR 0018/0019)
- [x] #13 (D) Placement + image creative behind approval gate — review state machine, four-eyes, immutable versions, image acceptance, audit (ADR 0020/0021)
- [x] #14 (E) Impression/click measurement + daily metrics CSV — append-only events, consent-gated visitor bucket, opt-out, no raw PII, CSV export (ADR 0012/0015/0017)
- [x] #15 (F) Locale CI — six-locale catalog check: nested-key parity + pluralization, GitHub Actions (ADR 0011)

**Phase 1 — Foundation ✅ complete (2026-06-04): #10–#15 all merged.**

## Next (Phase 2 — milestone "Phase 2: Rich creatives")

- [x] #24 (A) Video creative acceptance — MP4/WebM, poster, max duration, no autoplay-with-sound (ADR 0021)
- [x] #25 (B) HTML5 bundle + malware scan + sandbox/CSP + review queue — content policy, scan-clean submit gate, opaque frame token, strict-CSP sandbox (ADR 0020/0021)
- [x] #26 (C) Frequency cap (consent-gated visitor buckets) — per-bucket daily cap, fail-open without consent (ADR 0017)
- [x] #27 (D) Consent UI (six-locale) + DSR tooling — consent strings, consent_state recorded, export/erasure (additive tombstone) (ADR 0016/0017)
- [x] #28 (E) Reporting polish — JSON time-series metrics (CTR + fill rate), admin + service (measurement-spec)

**Phase 2 — Rich creatives ✅ complete (2026-06-04): #24–#28 all merged.**

## Next (Phase 3 — milestone "Phase 3: Marketplace")

- [x] #47 (A) Money primitives + Advertiser + PricingRule (versioned, JPY net, no tax/float) (ADR 0014/0015)
- [x] #48 (B) Campaign + budget + funding + billable spend accrual — derived reproducible spend, cap/pause_on_budget_exhausted (no overspend), only active+funded serves billable
- [x] #49 (C) BillingPeriod + tamper-evident SpendSnapshot + audited close — versioned/reproducible snapshot, immutable on close (re-close 409)
- [x] #50 (D) Invoice handoff (idempotent on external_reference) + reconciliation + HTTP client (net-only, failure-isolated)
- [x] #51 (E) Billing-relevant statutory retention + legal hold — two regimes, legal-hold gate, privileged audited purge (closes Integrity #41)

**Phase 3 — Marketplace ✅ complete (2026-06-04): #47–#51 all merged.**
**Integrity & audit hardening ✅ complete: #36–#41 (#41 closed by #51).**

## Next (Phase 4 — milestone "Phase 4: Ecosystem")

- [x] #57 (A) Upstream HTTP client framework + Records read client (asset metadata) (ADR 0002)
- [x] #58 (B) Deal opportunity handoff — net-only, idempotent on external_reference, audited, failure-isolated
- [ ] #59 (C) Concierge conversion beacon (append-only, no Contact submission) (ADR 0009)
- [ ] #60 (D) MCP write-plan mechanism (propose → confirm → apply, read-first, audited)

## Integrity & audit hardening (ADR 0022) — before Phase 3

- [x] Binding doc + ADR 0022 + self-review (`docs/explanation/audit-and-data-integrity-compliance.md`)
- [x] FK `ON DELETE CASCADE` → `RESTRICT` on governed tables (users/placements/creatives/audit_events) — #36; org delete now refused (error 1451), audit trail survives
- [x] Hash-chained tamper-evident `audit_events` — #37; per-tenant SHA-256 chain + verifier (edit/gap/reorder detectable)
- [x] Audited-write coverage + structured before→after; mutation+audit atomic — #38; TransactionManager (Null+Pdo), all mutating use cases wrapped, archive now audited
- [x] DB grants: app role without `DELETE`/`TRUNCATE` on governed tables; `archived_at`/`disabled_at` tombstones — #39; `database/grants.sql`, Placement.archive(), presentation table `user_preferences`
- [x] Sensitive-read audit (`include_sensitive`, DSR export, PII-link reads) — #40; `view_sensitive_metrics` gate + `metrics.read_sensitive` audit; aggregate reads unaudited
- [x] Retention + legal-hold purge process (privileged role, audited) — #51

## Governance hardening (2026-06-04)

- [x] Billing & accounting compliance (binding): `docs/explanation/billing-and-accounting-compliance.md`
- [x] ADR 0014 (Serve is not the books of account; money SSOT = Invoice; tax-neutral)
- [x] ADR 0015 (billing-relevant measurement integrity)
- [x] Self-review checklist: `docs/review/billing-compliance.md`
- [x] Hardened Invoice handoff contract; registered marketplace billing terminology
- [x] Privacy/data-protection (binding): `docs/explanation/privacy-and-ad-compliance.md`
- [x] ADR 0016 (self-hosted data-controller model), ADR 0017 (consent & lawful basis)
- [x] Self-review checklist: `docs/review/privacy-compliance.md`; registered privacy/consent terminology
- [x] API security (binding): `docs/explanation/api-security-spec.md`
- [x] ADR 0018 (API surface & auth model), ADR 0019 (token & redirect safety)
- [x] Self-review checklist: `docs/review/api-security.md`; registered Problem Details slugs + operationId stems
- [x] Creative review & safety (binding): `docs/explanation/creative-review-and-safety.md`
- [x] ADR 0020 (review workflow & approval gating), ADR 0021 (acceptance & sandbox safety)
- [x] Self-review checklist: `docs/review/creative-review.md`; registered review states / scan / creative Problem Details slugs

## Notes

- Engineering docs: **English**. UI: **en, ja, zh-Hans, ko, de, es**.
- Ports: **8910 / 8911 / 3392**.
- **Serve is tax-neutral and not the books of account** (ADR 0014/0015); Phase 3 marketplace is gated by `docs/review/billing-compliance.md`.
- **Operator is the data controller; privacy by default** (ADR 0016/0017); measurement changes are gated by `docs/review/privacy-compliance.md`.
- **Three separated API surfaces; fail closed** (ADR 0018/0019); endpoint changes are gated by `docs/review/api-security.md`.
- **Only approved creatives serve** (ADR 0020/0021); creative changes are gated by `docs/review/creative-review.md`.

Last updated: 2026-06-04 (#58 Deal handoff landed; next: #59 Concierge conversion beacon)
