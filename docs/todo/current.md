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
- [ ] #15 (F) Locale CI — six-locale catalog check (ADR 0011)

Order: #10 first (foundation); then #11 → #12 → #13/#14; #15 after #10.

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

Last updated: 2026-06-04 (#14 measurement + CSV landed; next: #15 locale CI)
