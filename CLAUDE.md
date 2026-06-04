# CLAUDE.md — NeNe Serve

Agent guide. Cursor rules: `.cursor/rules/`.

## Source of Truth

| Purpose | Document |
| --- | --- |
| Scope | `docs/explanation/scope-contract.md` |
| Measurement | `docs/explanation/measurement-spec.md` |
| Billing & accounting (binding) | `docs/explanation/billing-and-accounting-compliance.md` |
| Privacy & consent (binding) | `docs/explanation/privacy-and-ad-compliance.md` |
| API security (binding) | `docs/explanation/api-security-spec.md` |
| API contracts (OpenAPI 3.1) | `docs/api/` (public · admin · service) |
| Creative review & safety (binding) | `docs/explanation/creative-review-and-safety.md` |
| Audit & data integrity (binding) | `docs/explanation/audit-and-data-integrity-compliance.md` |
| i18n | `docs/development/i18n.md` |
| Tasks | `docs/todo/current.md` |

## Quick Rules

- Issue-driven; branch `type/issue-number-summary`; never commit to `main` directly
- English repo docs (ADR 0008); six locale message catalogs for UI (ADR 0011)
- No NeNe Clear / reconciliation / dunning domain in this repo
- **Serve is not the books of account; tax-neutral.** No tax computation, no qualified invoices; money SSOT = Invoice (ADR 0014). Billing-relevant counts are audit-grade (ADR 0015)
- **Operator is the data controller; privacy by default.** Consent-gated non-essential tracking, data minimization, no raw PII in event tables (ADR 0016/0017)
- **Three API surfaces** (public `/public/*` none · admin `/admin/*` JWT+Capability · service `/api/*` scoped token); no open redirect, no `*` CORS for credentialed routes, opaque short-lived tokens (ADR 0018/0019)
- **Only approved creatives serve.** Review workflow + four-eyes approval, immutable versions, html5 malware-scanned + sandboxed; no raw third-party tags (ADR 0020/0021)
- **Governed data is append-only & fully audited.** Every governed write is audited (who/when/before→after/why); no ad-hoc/manual physical delete — "delete" = archive/disable/tombstone; FKs `RESTRICT` not `CASCADE`; app DB role lacks `DELETE`/`TRUNCATE`; only cosmetic "presentation" data (allowlist) is freely deletable/unaudited (ADR 0022)
- MCP maps to Serve OpenAPI only (`docs/api/service.openapi.json`)
- **OpenAPI 3.1 is the contract** for the three surfaces (`docs/api/`, ADR 0018); `tests/Api/OpenApiContractTest.php` asserts documented paths == Kernel routes — add an endpoint, update its spec
- **Admin SPA in `frontend/`** follows the sibling NeNe convention (React+Vite+TS, FSD, Tailwind v4, TanStack Query, MSW mock-first, Storybook, six-locale i18n); typed from `docs/api/admin.openapi.json` via `npm run codegen`; gate is `npm run check` (see `frontend/README.md`)

## Ports

API **8910** · phpMyAdmin **8911** · MySQL **3392** · frontend dev **5189** · Storybook **6107** · Mailpit UI **8913** (SMTP 1025)

## Status

Phase 0 governance — see `docs/todo/current.md`.
