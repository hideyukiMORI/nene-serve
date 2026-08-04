# NeNe Serve

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](./LICENSE)
[![PHP 8.4](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php)](https://www.php.net/)

**Self-hosted ad serving and analytics — embed with one script line.**

**NeNe Serve** registers creatives (image, video, sandboxed HTML5), serves them into
**placements** on any site via **`serve.js`**, enforces weights and impression caps,
tracks impressions and clicks, and reports time-series metrics with CSV export — on
[NENE2](https://github.com/hideyukiMORI/NENE2).

> **Separate product.** Serve does **not** host contact forms
> ([`nene-contact`](https://github.com/hideyukiMORI/nene-contact)),
> chat scenarios ([`nene-concierge`](https://github.com/hideyukiMORI/nene-concierge)),
> or billing documents ([`nene-invoice`](https://github.com/hideyukiMORI/nene-invoice)).
> See [ADR 0009](./docs/adr/0009-separate-from-contact-and-concierge.md).

## Domain (binding)

| Product | Repository | What it does |
| --- | --- | --- |
| **NeNe Serve** | `nene-serve` (this) | Ad serving, placement rules, imp/click analytics |
| **NeNe Contact** | `nene-contact` | Embeddable contact forms and inbox |
| **NeNe Concierge** | `nene-concierge` | Scenario chat and step actions |
| **NeNe Invoice** | `nene-invoice` | Quotes, invoices, payments (advertiser billing handoff) |

## Goals

- **Creatives** — image, video, reviewed HTML5 bundles (no arbitrary third-party ad tags in MVP)
- **Placements** — `serve.js` embed with weighted rotation, caps, default fallback
- **Measurement** — server-side impressions, redirect-based clicks, daily charts, CSV
- **Six-locale admin & embed UI** — see [ADR 0011](./docs/adr/0011-six-locale-application.md)
- **OpenAPI + MCP** — campaign and weight management through documented HTTP only
- **Optional marketplace** (Phase 3+) — advertiser budgets; money SSOT stays in Invoice

## Non-goals

- Not a contact-form host or inquiry inbox — that's [`nene-contact`](https://github.com/hideyukiMORI/nene-contact)
- Not a chat-scenario / conversational engine — that's [`nene-concierge`](https://github.com/hideyukiMORI/nene-concierge)
- Not a billing SSOT — no tax computation, no qualified invoices, no payment ledger; money SSOT stays in [`nene-invoice`](https://github.com/hideyukiMORI/nene-invoice) (ADR 0014)
- Not bank-deposit reconciliation or collection reminders — that belongs to other siblings, not Serve
- Not a global RTB/DSP or third-party ad-tag network — only reviewed, sandboxed creatives serve (ADR 0020/0021)
- Not a shared database with sibling products — HTTP only (ADR 0002)

Full list: [`docs/explanation/scope-contract.md`](./docs/explanation/scope-contract.md) ("DON'T" table)

## Documentation (read first)

| Topic | Document |
| --- | --- |
| **Scope contract (GOAL / DO / DON'T)** | [`docs/explanation/scope-contract.md`](./docs/explanation/scope-contract.md) |
| **Measurement rules (binding)** | [`docs/explanation/measurement-spec.md`](./docs/explanation/measurement-spec.md) |
| **Embed / serve.js contract** | [`docs/explanation/serve-embed-spec.md`](./docs/explanation/serve-embed-spec.md) |
| **API & serve.js security (binding)** | [`docs/explanation/api-security-spec.md`](./docs/explanation/api-security-spec.md) |
| **API contracts (OpenAPI 3.1)** | [`docs/api/`](./docs/api/) (public · admin · service) |
| **Creative review & sandbox safety (binding)** | [`docs/explanation/creative-review-and-safety.md`](./docs/explanation/creative-review-and-safety.md) |
| **Privacy, data protection & consent (binding)** | [`docs/explanation/privacy-and-ad-compliance.md`](./docs/explanation/privacy-and-ad-compliance.md) |
| **Billing & accounting compliance (binding)** | [`docs/explanation/billing-and-accounting-compliance.md`](./docs/explanation/billing-and-accounting-compliance.md) |
| **Audit & data integrity (binding)** | [`docs/explanation/audit-and-data-integrity-compliance.md`](./docs/explanation/audit-and-data-integrity-compliance.md) |
| **i18n (six locales)** | [`docs/development/i18n.md`](./docs/development/i18n.md) |
| **Console help (reference)** | [`docs/reference/admin-console.md`](./docs/reference/admin-console.md) |
| **Tutorial (end-to-end)** | [`docs/tutorial/first-campaign.md`](./docs/tutorial/first-campaign.md) |
| **Deploying (partial — health checks, rate limit store)** | [`docs/how-to/deploy.md`](./docs/how-to/deploy.md) |
| **Design brief (for Claude Design)** | [`docs/design/design-brief.md`](./docs/design/design-brief.md) |
| **Terminology registry (binding)** | [`docs/explanation/terminology.md`](./docs/explanation/terminology.md) |
| **Sibling integrations** | [`docs/integrations/sibling-products.md`](./docs/integrations/sibling-products.md) |
| **Agents** | [`AGENTS.md`](./AGENTS.md) |

## Status

| Phase | Scope | Status |
| --- | --- | --- |
| 0 | Governance + product docs | ✅ |
| 1 | Foundation — multi-tenant org/auth, three API surfaces, placement + creative review, impression/click measurement, locale CI | ✅ |
| 2 | Rich creatives — video, HTML5 sandbox + malware scan, frequency cap, consent UI, reporting polish | ✅ |
| 3 | Marketplace — advertisers, versioned pricing, budgets, billing-period close, Invoice handoff, retention + legal hold | ✅ |
| 4 | Ecosystem — Records read, Deal handoff, Concierge conversion beacon, MCP write-plan | ✅ |
| — | Integrity & audit hardening (ADR 0022) — append-only governed data, hash-chained audit, DB-enforced no-delete | ✅ |
| v1 | Operable console — `serve.js` embed client, admin SPA (read screens + create forms), email-based provisioning, asset upload + ClamAV scan | ✅ |
| v1+ | Production hardening — billing-period actions/edit forms in the console, video/HTML5 upload UI, deploy hardening (migrations on deploy, HTTPS/secrets, shared token/rate-limit store for multi-host), real sibling integrations | 🔄 In progress |

**Money SSOT stays in NeNe Invoice; Serve is tax-neutral** (ADR 0014/0015). Quality
gates are green: PHPStan level 8, PSR-12, full backend and frontend test suites
(type-check, lint, knip, Storybook).

Details and sequencing: private `nene-origin/internal-docs/serve/todo/current.md` (operational logs live in the private receptacle).

## Running locally

```bash
# Docker (full stack: API + MySQL + phpMyAdmin + Mailpit + ClamAV)
docker compose up -d
curl http://127.0.0.1:8010/health        # {"status":"ok",...}

# Apply migrations, then least-privilege DB grants (ADR 0022: app role cannot
# DELETE/TRUNCATE governed tables):
#   for f in database/migrations/*.sql; do mysql ... < "$f"; done
#   mysql -uroot -p nene_serve < database/grants.sql

# Admin console (React+Vite SPA, proxies /admin·/api·/public to the API):
cd frontend && npm install && npm run dev   # http://localhost:5180

# Or run the API without Docker (PHP 8.4+):
composer install
composer serve              # php -S 127.0.0.1:8010 -t public_html
composer locales:check      # six-locale key parity (ADR 0011)
composer test               # PHPUnit (unit; serverless SQLite)
composer check              # test + PHPStan level 8 + PSR-12

# Integration suite against a real MySQL (native prepares, as in prod) — catches
# dialect-specific bugs SQLite can't. Skips when MYSQL_TEST_* is unset; CI runs it.
MYSQL_TEST_HOST=127.0.0.1 MYSQL_TEST_PORT=3380 MYSQL_TEST_DB=nene_serve \
  MYSQL_TEST_USER=root MYSQL_TEST_PASSWORD=root composer test:integration
```

## Local ports (fixed)

| Service | Host port |
| --- | --- |
| PHP / API | **8010** |
| phpMyAdmin | **8011** |
| MySQL | **3380** |
| Frontend dev (Vite) | **5180** |
| Storybook | **6107** |
| Mailpit UI (SMTP 1080) | **8013** |
| ClamAV (clamd) | **3308** |

NeNe Serve owns the **80xx** port lane; sibling products use their own lanes so several
apps can run locally side by side. Vite uses `strictPort` so the dev server never climbs
into a sibling's range. Full policy: [`CLAUDE.md#ports`](./CLAUDE.md#ports).

## Ecosystem layer

```
Front office:  Records · Corpus · Concierge · Contact · Serve (this)
Sales:         Deal → Invoice (advertiser billing handoff)
```

## License

MIT — see [LICENSE](./LICENSE).
