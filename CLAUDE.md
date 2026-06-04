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
- MCP maps to Serve OpenAPI only

## Ports

API **8910** · phpMyAdmin **8911** · MySQL **3392**

## Status

Phase 0 governance — see `docs/todo/current.md`.
