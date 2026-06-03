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
| **Privacy & ad compliance** | [`docs/explanation/privacy-and-ad-compliance.md`](./docs/explanation/privacy-and-ad-compliance.md) |
| **i18n (six locales)** | [`docs/development/i18n.md`](./docs/development/i18n.md) |
| **Terminology registry (binding)** | [`docs/explanation/terminology.md`](./docs/explanation/terminology.md) |
| **Sibling integrations** | [`docs/integrations/sibling-products.md`](./docs/integrations/sibling-products.md) |
| **Agents** | [`AGENTS.md`](./AGENTS.md) |

## Status

**Phase 0** — governance and product design. Runtime scaffold follows Issue #4+.

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
