# Measurement Spec (binding)

Defines **impression** and **click** for NeNe Serve analytics. Changing definitions requires an ADR amendment.

---

## Impression (MVP)

An **impression** is recorded when:

1. `serve.js` requested a creative from `GET /public/placements/{public_placement_key}/serve`, and
2. The API returned `200` with a creative payload, and
3. The client fired `POST /public/events/impression` (or equivalent beacon) with the issued `impression_token`.

**Not counted:** API errors, empty fallback with no creative, bots blocked by rate limit (optional `blocked` reason code).

Fields (minimum): `impression_id`, `placement_id`, `creative_id`, `organization_id`, `occurred_at`, `country_code` (optional), `placement_page_url` (truncated), `visitor_bucket` (hashed, no raw cookie in MVP).

---

## Click (MVP)

A **click** is recorded when:

1. Visitor requests `GET /public/clicks/{click_token}` (redirect endpoint), and
2. Serve logs the click and responds `302` to the creative's `destination_url`.

Direct links to `destination_url` bypassing redirect **do not** count as Serve clicks.

---

## Metrics derived

| Metric | Formula |
| --- | --- |
| CTR | `clicks / impressions` per placement/creative/day |
| Fill rate | `impressions with non-fallback creative / serve requests` |

---

## Reporting

- **Charts:** daily buckets in organization timezone (configurable, default UTC).
- **CSV columns:** documented in OpenAPI `ExportMetrics` schema (Phase 1+).
- **No real-time sub-second dashboards** in MVP.

---

## MCP / AI

- MCP read tools expose aggregated metrics only by default.
- Raw IP or visitor identifiers are **excluded** unless admin tool sets `include_sensitive=true` (audit logged).

---

## Related

- ADR 0012
- [`privacy-and-ad-compliance.md`](./privacy-and-ad-compliance.md)

Last updated: 2026-06-03
