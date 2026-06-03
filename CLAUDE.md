# CLAUDE.md — NeNe Serve

Agent guide. Cursor rules: `.cursor/rules/`.

## Source of Truth

| Purpose | Document |
| --- | --- |
| Scope | `docs/explanation/scope-contract.md` |
| Measurement | `docs/explanation/measurement-spec.md` |
| i18n | `docs/development/i18n.md` |
| Tasks | `docs/todo/current.md` |

## Quick Rules

- Issue-driven; branch `type/issue-number-summary`; never commit to `main` directly
- English repo docs (ADR 0008); six locale message catalogs for UI (ADR 0011)
- No NeNe Clear / reconciliation / dunning domain in this repo
- MCP maps to Serve OpenAPI only

## Ports

API **8910** · phpMyAdmin **8911** · MySQL **3392**

## Status

Phase 0 governance — see `docs/todo/current.md`.
