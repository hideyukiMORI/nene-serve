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
| Video poster image | `poster_url` | string (https) |
| Video duration | `duration_seconds` | integer |
| Campaign / delivery group | `campaign` | `Campaign` |
| Delivery plan | `delivery_plan` | `DeliveryPlan` |
| Weight share | `weight` | integer ≥ 0 |
| Impression cap | `max_impressions` | integer |
| Frequency cap (per visitor/day) | `frequency_cap` | integer (consent-gated) |
| Default creative | `default_creative_id` | FK |
| Impression event | `impression` | `Impression` |
| Click event | `click` | `Click` |
| Click redirect token | `click_token` | string |
| Creative review status | `review_status` | enum: `draft`, `submitted`, `in_review`, `approved`, `rejected`, `changes_requested` |
| Creative version | `creative_version` | integer ≥ 1 |
| Review reason | `review_reason` | string |
| HTML5 bundle id | `bundle_id` | string |
| Bundle byte size | `bundle_size_bytes` | integer |
| Malware scan status | `scan_status` | enum: `pending`, `clean`, `flagged` |
| Self-approval override | `self_approval_override` | boolean (audited) |
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

## Privacy & consent

Governed by [`privacy-and-ad-compliance.md`](./privacy-and-ad-compliance.md)
(binding), ADR 0016, ADR 0017.

| Concept | JSON / table | PHP |
| --- | --- | --- |
| Hashed visitor identifier | `visitor_bucket` | string (hashed) |
| Per-placement tracking switch | `measurement_enabled` | boolean |
| Consent mode | `consent_mode` | enum: `required`, `not_required` |
| Recorded consent signal | `consent_state` | enum: `granted`, `denied`, `unknown` |
| Lawful basis | `lawful_basis` | enum: `consent`, `legitimate_interest`, `not_applicable` |
| Retention window (days) | `retention_days` | integer |
| Data subject request | `data_subject_request` | `DataSubjectRequest` |
| DSR kind | `dsr_kind` | enum: `export`, `erasure` |
| Erasure tombstone | `erased_at` | timestamp (additive, never a count edit) |
| Truncated page URL | `placement_page_url` | string (truncated) |
| Country code | `country_code` | string (ISO 3166-1 alpha-2) |

**Forbidden in event tables (raw PII):** `email`, `ip_address` (long-term raw),
precise geo coordinates, cross-publisher fingerprints.

---

## Layer suffixes

`Handler`, `UseCase`, `RepositoryInterface`, `Pdo*Repository` — never `Controller`, `Service`, `Repo`.

---

## Audit & data integrity (ADR 0022)

Governed by [`audit-and-data-integrity-compliance.md`](./audit-and-data-integrity-compliance.md)
(binding).

**Governed data** (default — append-only, no hard delete, all writes audited):
placements, creatives, campaigns, delivery plans, users/roles, advertisers/budgets,
impressions/clicks/serve requests, consent records, audit events.

**Presentation data** (allowlist — freely editable/deletable, not audited): UI
theme, dashboard layout, column order, saved filters, a user's display-locale
preference (`user_preferences.locale`). Add here only via a PR explaining why the
data has no delivery/measurement/billing/identity meaning.

| Concept | JSON / column | Notes |
| --- | --- | --- |
| Archive tombstone | `archived_at` | additive; replaces hard delete |
| Disable tombstone | `disabled_at` | additive; e.g. users, tokens |
| Erasure tombstone | `erased_at` | DSR; forgets the link, keeps the count (ADR 0017) |
| Audit before-state | `before` | in audit metadata |
| Audit after-state | `after` | in audit metadata |

**Audit `action` naming:** `{subject}.{verb}` snake_case — e.g. `creative.approved`,
`placement.created`, `placement.archived`, `user.created`, `budget.changed`,
`period.closed`, `dsr.erasure`, `dsr.export`, `metrics.read_sensitive`,
`advertiser.created`, `pricing_rule.created`, `campaign.created`,
`billing_period.opened`, `billing_period.closed`, `invoice.reconciled`,
`invoice.handed_off`, `invoice.handoff_failed`, `invoice.reconciliation_discrepancy`.
Register
new actions before use. Mutation audit metadata carries structured
`before`/`after` (changed fields). Sensitive **reads** (`include_sensitive`
metrics, DSR export, PII-link reads) are also audited; ordinary reads are not.

Tenant/parent foreign keys on governed tables use **`ON DELETE RESTRICT`** (never
`CASCADE`). The application DB role has no `DELETE`/`TRUNCATE` on governed tables.

---

## Campaign / placement status

| Value | Meaning |
| --- | --- |
| `draft` | Not served |
| `active` | Eligible for serve API |
| `paused` | Temporarily off |
| `archived` | Historical only |

Creative **`review_status`** (separate axis, ADR 0020): `draft`, `submitted`,
`in_review`, `approved`, `rejected`, `changes_requested`. Only `approved`
creatives in an `active` campaign serve. Approval needs the **`review_creatives`**
capability; self-approval is disallowed by default.

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

## Roles & capabilities (ADR 0006)

Roles map to capabilities; admin routes require a **capability**, not a role.
`superadmin` is the only cross-tenant role and implicitly holds every capability.

| Capability | `superadmin` | `org_admin` | `editor` | `analyst` |
| --- | :---: | :---: | :---: | :---: |
| `view_users` | ✓ | ✓ | | |
| `manage_users` | ✓ | ✓ | | |
| `view_metrics` | ✓ | ✓ | ✓ | ✓ |
| `view_sensitive_metrics` | ✓ | ✓ | | |
| `manage_settings` | ✓ | ✓ | | |
| `manage_placements` | ✓ | ✓ | ✓ | |
| `manage_creatives` | ✓ | ✓ | ✓ | |
| `review_creatives` | ✓ | ✓ | | |
| `manage_marketplace` | ✓ | ✓ | | |

Role values (`users.role`): `superadmin`, `org_admin`, `editor`, `analyst`.
Capability strings are snake_case and registered here before use.

---

## Service-token scopes (ADR 0018 §5)

Service tokens (`/api/*`) grant explicit scopes, not human capabilities; MCP is
read-first. `insufficient-scope` → 403. Register before use.

| Scope | Grants |
| --- | --- |
| `read:placements` | List/read placements (tenant-scoped) |
| `read:metrics` | Read aggregated metrics / CSV export |
| `write:delivery_plan` | Propose/apply delivery-plan changes (audited) |

---

## API surfaces (ADR 0018)

| Surface | Path | Auth |
| --- | --- | --- |
| Public serve | `/public/*` | none (origin-gated, rate-limited) |
| Admin | `/admin/*` | JWT + `Capability` (ADR 0006) |
| Service | `/api/*` | scoped service token |
| System | `GET /health` | none |

---

## Problem Details type slugs (kebab-case)

Base URL: `https://nene-serve.dev/problems/`. Register before use.

| Slug | Use |
| --- | --- |
| `validation-failed` | Request body/field validation error (422) |
| `placement-not-found` | Public placement key not found (404) |
| `creative-not-found` | Creative id not found (404) |
| `creative-not-approved` | Creative is not in `approved` state for the action (409) |
| `invalid-review-transition` | Disallowed review-status change (409) |
| `self-approval-forbidden` | Submitter cannot approve own creative without override (403) |
| `creative-scan-failed` | HTML5 bundle scan is not `clean` (422) |
| `destination-url-not-registered` | Redirect target not registered on the creative (422) |
| `origin-not-allowed` | Request `Origin` not in placement `allowed_origins` (403) |
| `click-token-invalid` | Click token expired, used, or unknown (404 / 410) |
| `too-many-requests` | Rate limit exceeded (429) |
| `unauthorized` | Missing/invalid bearer token (401) |
| `insufficient-capability` | Authenticated human lacks required capability (403) |
| `insufficient-scope` | Service token lacks required scope (403) |
| `organization-not-resolved` | Tenant could not be resolved (404) |
| `organization-mismatch` | User org ≠ URL-resolved org (403) |
| `route-not-found` | No route matched the request path (404) |
| `campaign-not-found` | Campaign id not found in the tenant (404) |
| `billing-period-not-found` | Billing period id not found in the tenant (404) |
| `invalid-period-transition` | Disallowed billing-period change, e.g. re-closing (409) |
| `reconciliation-failed` | Snapshot did not reconcile; handoff refused (409) |
| `invoice-handoff-failed` | Invoice transport failed; not paused, retryable (502) |

Validation `errors[].field` uses snake_case paths; `errors[].code` is snake_case.

---

## operationId stems (camelCase)

Shape `{verb}{Resource}` / `{verb}{Resource}ById`. Stable after release; must
match across OpenAPI, routes, and MCP tool catalog.

| operationId | Surface |
| --- | --- |
| `getHealth` | System |
| `serveCreative`, `recordImpression`, `redirectClick`, `getCreativeFrame` | Public serve |
| `login`, `getCurrentUser` | Admin auth |
| `listUsers` | Admin (tenant-scoped) |
| `listPlacements`, `getPlacementById`, `createPlacement`, `updatePlacement` | Admin |
| `createPlacement`, `archivePlacement` | Admin (`manage_placements`) |
| `listCreatives`, `createCreative`, `reviseCreative` | Admin (`manage_creatives`) |
| `submitCreative` | Admin (`manage_creatives`) |
| `startCreativeReview`, `approveCreative`, `rejectCreative`, `requestCreativeChanges`, `listReviewQueue` | Admin (`review_creatives`) |
| `publishCreative` | Admin |
| `getDeliveryPlan`, `updateDeliveryPlan` | Admin |
| `getPlacementMetrics`, `exportMetrics` | Admin / Service (read) |
| `createDataSubjectRequest` | Admin (`manage_settings`) |
| `createAdvertiser`, `listAdvertisers`, `createPricingRule` | Admin (`manage_marketplace`) |
| `createCampaign`, `getCampaign` | Admin (`manage_marketplace`) |
| `openBillingPeriod`, `closeBillingPeriod`, `getBillingPeriod`, `handoffBillingPeriod` | Admin (`manage_marketplace`) |

---

## MCP tools (planned naming)

`listServePlacements`, `getPlacementMetrics`, `proposeDeliveryPlanChange`, …
(read-first; audited writes; Serve OpenAPI only — ADR 0018).

---

## Environment variables (security)

| Variable | Purpose |
| --- | --- |
| `NENE_SERVE_JWT_SECRET` | Admin JWT signing secret (`.env` only) |
| `NENE_SERVE_CLICK_TOKEN_TTL` | Click token TTL; default `900` (15 min) |

Last updated: 2026-06-04
