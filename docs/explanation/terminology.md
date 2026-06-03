# Terminology Registry (binding)

Single source of truth for identifiers. Register new terms in the same PR.

---

## Product

| Term | Value |
| --- | --- |
| Product name | **NeNe Serve** |
| Repository | `nene-serve` |
| PHP namespace | `NeneServe\` |
| Embed script | `serve.js` (not `embed.js` — reserved for Contact) |

---

## Core entities

| Concept | JSON / table | PHP |
| --- | --- | --- |
| Placement (ad slot) | `placement` | `Placement` |
| Public placement key | `public_placement_key` | string |
| Creative | `creative` | `Creative` |
| Creative type | `creative_type` | enum: `image`, `video`, `html5_bundle` |
| Campaign / delivery group | `campaign` | `Campaign` |
| Delivery plan | `delivery_plan` | `DeliveryPlan` |
| Weight share | `weight` | integer ≥ 0 |
| Impression cap | `max_impressions` | integer |
| Default creative | `default_creative_id` | FK |
| Impression event | `impression` | `Impression` |
| Click event | `click` | `Click` |
| Click redirect token | `click_token` | string |
| Advertiser (Phase 3+) | `advertiser` | `Advertiser` |
| Budget (Phase 3+) | `budget_cents` | integer |

**Forbidden names for ad events:** `submission`, `inquiry`, `message` (Contact domain).

---

## Layer suffixes

`Handler`, `UseCase`, `RepositoryInterface`, `Pdo*Repository` — never `Controller`, `Service`, `Repo`.

---

## Campaign / placement status

| Value | Meaning |
| --- | --- |
| `draft` | Not served |
| `active` | Eligible for serve API |
| `paused` | Temporarily off |
| `archived` | Historical only |

---

## JSON

- snake_case for all API properties
- Monetary: `*_cents` integers only

---

## Locales (application)

`en`, `ja`, `zh-Hans`, `ko`, `de`, `es` — see ADR 0011.

---

## Environment variables (planned)

| Variable | Purpose |
| --- | --- |
| `NENE_SERVE_PORT` | Default `8910` |
| `NENE_INVOICE_API_BASE_URL` | Advertiser billing handoff |
| `NENE_INVOICE_SERVICE_TOKEN` | Scoped Invoice `/api/*` |

---

## MCP tools (planned naming)

`listServePlacements`, `getPlacementMetrics`, `proposeDeliveryPlanChange`, …

Last updated: 2026-06-03
