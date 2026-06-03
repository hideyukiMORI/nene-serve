# Measurement Spec (binding)

Defines **impression** and **click** for NeNe Serve analytics. Changing definitions requires an ADR amendment.

---

## Impression (MVP)

An **impression** is recorded when:

1. `serve.js` requested a creative from `GET /public/placements/{public_placement_key}/serve`, and
2. The API returned `200` with a creative payload, and
3. The client fired `POST /public/events/impression` (or equivalent beacon) with the issued `impression_token`.

**Not counted:** API errors, empty fallback with no creative, bots blocked by rate limit (optional `blocked` reason code).

**Consent gating:** non-essential measurement beacons (cross-visit identifiers,
frequency-cap `visitor_bucket`) **MUST NOT** fire before required consent, and not
at all when `measurement_enabled=false` — see
[`privacy-and-ad-compliance.md`](./privacy-and-ad-compliance.md) §3 and
[ADR 0017](../adr/0017-consent-and-lawful-basis.md). Essential serving is
unaffected.

Fields (minimum): `impression_id`, `placement_id`, `creative_id`, `organization_id`, `occurred_at`, `country_code` (optional), `placement_page_url` (truncated), `visitor_bucket` (hashed, no raw cookie in MVP).

---

## Click (MVP)

A **click** is recorded when:

1. Visitor requests `GET /public/clicks/{click_token}` (redirect endpoint), and
2. Serve logs the click and responds `302` to the creative's `destination_url`.

Direct links to `destination_url` bypassing redirect **do not** count as Serve clicks.

---

## Billable vs non-billable (marketplace mode)

When a count can incur advertiser spend it is **billing-relevant** and held to the
integrity standard in
[`billing-and-accounting-compliance.md`](./billing-and-accounting-compliance.md)
and [ADR 0015](../adr/0015-billing-relevant-measurement-integrity.md).

A **billable** event is a counted impression/click eligible to incur spend. The
following are **non-billable** and **MUST NOT** accrue spend:

- Fallback / default-creative serves (no funded advertiser behind them)
- API errors and empty serves
- Bot / invalid-traffic filtered events (recorded with a reason code, never
  dropped silently)
- Placements with `measurement_enabled=false`
- Events outside an **active, funded** campaign window
- Serves of a creative that is not `approved` (creative review — ADR 0020)

Reporting and billing use the **same** definition and the **same** event records.
Once a `billing_period` is **closed**, its billable counts are **immutable**;
corrections are additive adjustments in a later period, never edits to closed
figures.

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
- Advertisers (marketplace) receive **aggregated metrics only** — never raw visitor identifiers (privacy doc N8).

---

## Related

- ADR 0012, ADR 0015 (billing-relevant integrity)
- [`privacy-and-ad-compliance.md`](./privacy-and-ad-compliance.md)
- [`billing-and-accounting-compliance.md`](./billing-and-accounting-compliance.md) (binding)

Last updated: 2026-06-04
