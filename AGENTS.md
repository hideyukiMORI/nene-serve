# Agent / AI Guide

Entry point for AI agents working on **NeNe Serve** (public repo `nene-serve`).

## Domain (read first)

| Product | Repository | Domain |
| --- | --- | --- |
| **NeNe Serve** | `nene-serve` (this) | Ad serving, placements, imp/click analytics |
| **NeNe Contact** | `nene-contact` | Contact forms and inbox |
| **NeNe Concierge** | `nene-concierge` | Scenario chat |
| **NeNe Invoice** | `nene-invoice` | Billing (advertiser money handoff only) |

See [ADR 0009](docs/adr/0009-separate-from-contact-and-concierge.md).

## Read First

- **Scope contract (binding):** `docs/explanation/scope-contract.md`
- **Measurement spec (binding):** `docs/explanation/measurement-spec.md`
- **Serve embed spec (binding):** `docs/explanation/serve-embed-spec.md`
- **Privacy & ad compliance (binding):** `docs/explanation/privacy-and-ad-compliance.md`
- **Billing & accounting compliance (binding):** `docs/explanation/billing-and-accounting-compliance.md`
- **Six locales (binding):** `docs/adr/0011-six-locale-application.md`, `docs/development/i18n.md`
- **Terminology registry (binding):** `docs/explanation/terminology.md`
- **Sibling integrations:** `docs/integrations/sibling-products.md`
- **Current work:** `docs/todo/current.md`

## Operating Rules

- Issue-driven; no direct commits to `main`
- **Identifiers** must match `docs/explanation/terminology.md`
- Do **not** add contact forms, chat scenarios, bank CSV, reconciliation, or invoice issuance
- **No tax computation and no qualified invoices in Serve; money SSOT = Invoice** (ADR 0014). Billing-relevant counts are append-only, closed-period immutable, reconciled, idempotent (ADR 0015)
- **Repository engineering docs: English only** (ADR 0008). **UI strings: six locales** in `locales/`
- Namespace: `NeneServe\`
- Siblings via **HTTP only** (ADR 0002)

## Framework

[NENE2](https://github.com/hideyukiMORI/NENE2) via Composer when runtime lands.
