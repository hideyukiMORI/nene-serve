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
| **Terminology registry (binding)** | [`docs/explanation/terminology.md`](./docs/explanation/terminology.md) |
| **Sibling integrations** | [`docs/integrations/sibling-products.md`](./docs/integrations/sibling-products.md) |
| **Agents** | [`AGENTS.md`](./AGENTS.md) |

## Status

**Phase 3 — Marketplace ✅ complete** (#47–#51), on top of Phase 1 (#10–#15) and
Phase 2 (#24–#28): advertisers, versioned pricing, campaigns with budgets +
billable spend accrual (no overspend), tamper-evident spend snapshots + immutable
billing-period close, idempotent net Invoice handoff + reconciliation, and
statutory retention + legal hold. **Money SSOT stays in NeNe Invoice; Serve is
tax-neutral** (ADR 0014/0015). Also shipped: **integrity & audit hardening**
(#36–#41) — append-only governed data, hash-chained audit, DB-enforced no-delete —
and **Phase 4 — Ecosystem** (#57–#60): Records read, Deal opportunity handoff,
Concierge conversion beacon, and an MCP write-plan mechanism (propose→confirm→apply,
read-first, audited; Serve OpenAPI only).

## Running locally

```bash
# Docker (full stack: API + MySQL + phpMyAdmin)
docker compose up -d
curl http://127.0.0.1:8910/health        # {"status":"ok",...}

# Apply migrations, then least-privilege DB grants (ADR 0022: app role cannot
# DELETE/TRUNCATE governed tables):
#   for f in database/migrations/*.sql; do mysql ... < "$f"; done
#   mysql -uroot -p nene_serve < database/grants.sql

# Or without Docker (PHP 8.3+):
composer install            # or `composer dump-autoload` for the scaffold only
composer serve              # php -S 127.0.0.1:8910 -t public_html
composer locales:check      # six-locale key parity (ADR 0011)
composer test               # PHPUnit
```

## Local ports (fixed)

| Service | Host port |
| --- | --- |
| PHP / API | **8910** |
| phpMyAdmin (when added) | **8911** |
| MySQL (when added) | **3392** |

Use the **891x** lane. Do not collide with NeNe Contact (**8900**) or other portfolio ports.

## Ecosystem layer

```
Front office:  Records · Corpus · Concierge · Contact · Serve (this)
Sales:         Deal → Invoice (advertiser billing handoff)
```

## License

MIT — see [LICENSE](./LICENSE).
