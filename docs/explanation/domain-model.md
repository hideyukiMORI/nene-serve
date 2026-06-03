# Domain Model (Phase 0)

## Tenant

- **Organization**, **User**, roles (ADR 0006)

## Serving core

- **Placement** — `public_placement_key`, `allowed_origins`, locale defaults
- **Creative** — type, asset URLs or bundle id, `destination_url`, review status
- **Campaign** — groups creatives; schedule; status
- **DeliveryPlan** — weights, caps, `default_creative_id`
- **Impression**, **Click** — append-only events (partition-friendly)

## Marketplace (Phase 3+)

- **Advertiser**, **Budget** (`budget_cents`), link to Invoice `external_reference`

## Audit

- **AuditEvent** — plan changes, creative publish, MCP writes

No `submission`, `invoice`, `bank_transaction`, or `scenario` tables in Serve DB.

Last updated: 2026-06-03
