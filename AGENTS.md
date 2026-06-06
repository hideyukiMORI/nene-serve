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
- **API & serve.js security (binding):** `docs/explanation/api-security-spec.md`
- **Creative review & sandbox safety (binding):** `docs/explanation/creative-review-and-safety.md`
- **Six locales (binding):** `docs/adr/0011-six-locale-application.md`, `docs/development/i18n.md`
- **Terminology registry (binding):** `docs/explanation/terminology.md`
- **Sibling integrations:** `docs/integrations/sibling-products.md`
- **Current work:** `docs/todo/current.md`

## Operating Rules

- Issue-driven; no direct commits to `main`
- **Identifiers** must match `docs/explanation/terminology.md`
- Do **not** add contact forms, chat scenarios, bank CSV, reconciliation, or invoice issuance
- **No tax computation and no qualified invoices in Serve; money SSOT = Invoice** (ADR 0014). Billing-relevant counts are append-only, closed-period immutable, reconciled, idempotent (ADR 0015)
- **Operator is the data controller; privacy by default** (ADR 0016/0017): consent-gate non-essential beacons, minimize data, hash visitor identifiers, no raw PII in event tables, advertisers get aggregates only
- **Keep API surfaces separate** (ADR 0018/0019): `/public/*` unauthenticated + origin-gated + rate-limited, `/admin/*` JWT+Capability, `/api/*` scoped token; no open redirect, no `*` CORS for credentialed routes, opaque idempotent tokens, no secrets/PII in public responses
- **Only approved creatives serve** (ADR 0020/0021): respect the review state machine, four-eyes approval (no self-approval by default), immutable approved versions (edits → new version → re-review), html5 bundles malware-scanned + sandboxed iframe + strict CSP; never raw `third_party_tag`
- **Repository engineering docs: English only** (ADR 0008). **UI strings: six locales** in `locales/`
- Namespace: `NeneServe\`
- Siblings via **HTTP only** (ADR 0002)

## Framework

[NENE2](https://github.com/hideyukiMORI/NENE2) via Composer (`hideyukimori/nene2`,
path repository `../NENE2`); runtime is wired through `Http\RuntimeContainerFactory`.

## Frontend

Admin SPA in `frontend/` follows the sibling NeNe convention (React+Vite+TS, Feature-Sliced,
Tailwind v4, TanStack Query, react-hook-form+zod, MSW mock-first, Storybook, six-locale i18n
per ADR 0011, `openapi-typescript` codegen). Dev port **8915**, Storybook **6107**; backend
proxied from `APP_PORT` (8910). Quality gate: `npm run check`. See `frontend/README.md`.
