# Current TODO

**Phase 0 — Governance** ✅ complete on `main` (2026-06-03)

## Phase 0 checklist

- [x] Public repo scaffold + six locale placeholders
- [x] Scope contract, measurement spec, serve.js spec, privacy/ad compliance
- [x] ADRs 0001–0013 (incl. six-locale, Contact/Concierge split, creative sandbox)
- [x] Sibling map (Invoice handoff draft; no Clear/Profile paths)
- [x] GitHub Issue #1 closed

## Next (Phase 1)

- [ ] #4 Runtime scaffold — NENE2, `GET /health`, `composer locales:check` stub
- [ ] #5 Multi-tenant + auth
- [ ] #6 OpenAPI baseline

## Governance hardening (2026-06-04)

- [x] Billing & accounting compliance (binding): `docs/explanation/billing-and-accounting-compliance.md`
- [x] ADR 0014 (Serve is not the books of account; money SSOT = Invoice; tax-neutral)
- [x] ADR 0015 (billing-relevant measurement integrity)
- [x] Self-review checklist: `docs/review/billing-compliance.md`
- [x] Hardened Invoice handoff contract; registered marketplace billing terminology

## Notes

- Engineering docs: **English**. UI: **en, ja, zh-Hans, ko, de, es**.
- Ports: **8910 / 8911 / 3392**.
- **Serve is tax-neutral and not the books of account** (ADR 0014/0015); Phase 3 marketplace is gated by `docs/review/billing-compliance.md`.

Last updated: 2026-06-04
