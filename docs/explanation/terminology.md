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

## Marketplace billing (Phase 3+)

Governed by [`billing-and-accounting-compliance.md`](./billing-and-accounting-compliance.md)
(binding), ADR 0014, ADR 0015. All money: integer `*_cents`, **net of tax**.

| Concept | JSON / table | PHP |
| --- | --- | --- |
| Spent to date (net) | `spent_cents` | integer ≥ 0 |
| Billable impression | `billable_impression` | derived |
| Billable click | `billable_click` | derived |
| Non-billable reason code | `non_billable_reason` | enum: `fallback`, `error`, `bot_filtered`, `opt_out`, `unfunded` |
| Billing period | `billing_period` | `BillingPeriod` |
| Spend snapshot (versioned) | `spend_snapshot` | `SpendSnapshot` |
| Pricing rule + version | `pricing_rule`, `pricing_rule_version` | `PricingRule` |
| Pricing model | `pricing_model` | enum: `cpm`, `cpc`, `flat` |
| Reconciliation run | `reconciliation_run` | `ReconciliationRun` |
| Invoice handoff reference | `external_reference` | string (idempotency key) |
| Invoice linkage | `invoice_client_id`, `invoice_payment_id` | FK (Invoice-side ids) |
| Auto-pause flag | `pause_on_budget_exhausted` | boolean |

**Forbidden in Serve (tax/accounting — belong to Invoice):** `tax_cents`,
`tax_rate_bps`, `is_qualified_invoice`, `registration_number`, `invoice_number`,
`subtotal_cents`/`total_cents` *as document totals*. Serve stores **net spend
counters only**.

### Billing period / campaign funding status

| Owner | Values |
| --- | --- |
| `billing_period.status` | `open`, `closed`, `reconciled`, `handed_off` |
| campaign funding | `unfunded`, `funded`, `exhausted` |

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

Last updated: 2026-06-04
