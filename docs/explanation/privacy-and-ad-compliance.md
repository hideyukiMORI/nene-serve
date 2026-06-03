# Privacy, Data Protection & Ad Compliance — Binding Rules

**Status: binding (non-negotiable).** Source of truth for lawful, transparent ad
measurement in NeNe Serve. A privacy or data-protection reviewer must be able to
find **zero deviations** from the rules below. **Not legal advice.**

These are **MUST** requirements. Where a rule here conflicts with UX,
performance, or implementation convenience, **compliance wins** — every time.

Read first: [`scope-contract.md`](./scope-contract.md),
[`measurement-spec.md`](./measurement-spec.md),
[`billing-and-accounting-compliance.md`](./billing-and-accounting-compliance.md),
[ADR 0016](../adr/0016-self-hosted-data-controller-model.md),
[ADR 0017](../adr/0017-consent-and-lawful-basis.md), ADR 0010 (public API
security), self-review [`../review/privacy-compliance.md`](../review/privacy-compliance.md).

---

## 0. Governing principles

1. **Privacy by design and by default.** The default configuration collects the
   **minimum** data needed to serve and measure; richer tracking is opt-in and
   consent-gated, never the default.
2. **The operator is the data controller.** NeNe Serve is **self-hosted
   software** the operator runs on their own infrastructure. By default **no
   visitor data leaves the operator's infrastructure** (ADR 0016). There is no
   NeNe-operated data collection.
3. **Jurisdiction-neutral software.** Serve does not encode one country's law; it
   provides the **controls** operators need to comply with their regime — see §1.
   Engineering is not the legal authority; when unclear, **stop and consult**.
4. **No silent deviation.** Any departure from this document requires an **ADR**
   with privacy/legal sign-off recorded in it. Code may not merge a deviation
   without it.
5. **Single source of truth for consent and retention state.** Consent decisions
   and retention windows are evaluated **once** and honored consistently by the
   serve path, the beacon path, reporting, and exports.

---

## 1. Statutory posture (what we provide controls for)

Serve targets operators across the six application locales, so it provides
controls to support — without claiming to be legal advice — at least:

| Regime | Markets | What Serve provides |
| --- | --- | --- |
| **GDPR** | EU (`de`, `es`, …) | Lawful-basis/consent gating, data-subject-request tooling, minimization, retention limits |
| **ePrivacy / cookie consent** | EU | Consent **before** non-essential storage/identifiers; essential serving without consent |
| **個人情報保護法 (APPI)** | Japan (`ja`) | Purpose limitation, retention, disclosure/erasure handling |
| **Generic** | `en`, `ko`, others | Same minimization + consent + retention controls |

When a regime changes (new consent rule, new retention duration, new data-subject
right), treat the gap as a privacy defect and open an Issue.

---

## 2. Roles and data flow

| Party | Role | Notes |
| --- | --- | --- |
| **Operator / publisher** | **Data controller** | Decides purposes; publishes privacy policy; obtains consent; responds to data-subject requests |
| **NeNe Serve (software)** | Tool run by the controller | Self-hosted; no third-party sharing by default |
| **Advertiser** (marketplace, Phase 3+) | Separate recipient | Receives **aggregated metrics only**; no raw visitor identifiers |
| **Siblings via HTTP** (Invoice/Records/…) | Operator-controlled endpoints | ADR 0002; handoff carries billing identity/aggregates, not visitor tracking data |

Cross-border transfer: with self-hosting there is **no default transfer**. Any
optional sibling HTTP call stays within infrastructure the operator controls.

---

## 3. Lawful basis & consent (ADR 0017)

- **Essential serving works without tracking.** Returning a creative and
  rendering it does not require consent.
- **Non-essential measurement is consent-gated where law requires it** — this
  includes cross-visit identifiers, frequency-cap visitor buckets, and any
  persistent client storage. Beacons for non-essential measurement **MUST NOT**
  fire before a positive consent signal where the regime requires consent.
- `measurement_enabled=false` on a placement serves the creative **without**
  tracking beacons (P2).
- The lawful basis and consent state in effect are **recorded** with the
  configuration, not inferred ad hoc.
- **No dark patterns:** consent UI (six-locale chrome) must offer refusal as
  easily as acceptance; pre-ticked consent is prohibited.

---

## 4. Data minimization & pseudonymization

### DO

| # | Rule |
| --- | --- |
| P1 | Document **measurement cookies/storage** in operator-facing privacy template (six locales) |
| P2 | Support **opt-out** per placement (`measurement_enabled=false` serves creative without tracking beacons) |
| P3 | **Retention** limits on impression/click rows (configurable per org) — see §6 |
| P4 | **Hash or bucket** visitor identifiers (`visitor_bucket`); no raw email in event tables |
| P5 | **Allowed origins** on every placement (ADR 0010) |
| P6 | **Advertiser billing** only via Invoice handoff — aggregates + identity, no visitor tracking data |
| P7 | **Audit** changes to weights, caps, active creatives, and consent/retention settings |
| P8 | **Export and delete** metrics on operator/data-subject request (DSR tooling Phase 2+) |
| P9 | Store only **truncated** page URLs; **country_code** instead of precise geo |

### Collected vs never collected

- **Collected (minimized):** `impression_id`/`click_token`, `placement_id`,
  `creative_id`, `organization_id`, `occurred_at`, optional `country_code`,
  truncated `placement_page_url`, hashed `visitor_bucket`.
- **Never stored:** raw email or other direct identifiers in event tables; full
  URLs containing tokens/query secrets; long-term raw IP; cross-publisher
  fingerprints without disclosure.

---

## 5. Data subject rights

- **Access / export** and **erasure** of a visitor's measurement data on
  operator/data-subject request (tooling Phase 2+).
- Erasure is an **additive tombstone**, not a figure edit. Where data is also
  **billing-relevant** (§6 / billing doc §7), the minimal statutory
  substantiation is **retained** under a documented carve-out and the rest is
  erased — billing retention and privacy erasure are reconciled explicitly, never
  by silently mutating counts.

---

## 6. Retention — two distinct regimes

| Data | Regime | Rule |
| --- | --- | --- |
| **Ordinary measurement** (no advertiser money behind it) | **Privacy-first** | Minimized, configurable per org, **shortest viable** window; not kept "just in case" |
| **Billing-relevant** events / spend snapshots | **Statutory** | Retained for the money SSOT's statutory period (JP 7y, up to 10y) — see [`billing-and-accounting-compliance.md`](./billing-and-accounting-compliance.md) §7 |

**Do not conflate the two.** Ordinary analytics must not inherit the long
billing retention; billing-relevant records must not be purged on the short
analytics schedule.

---

## 7. DON'T

| # | Rule |
| --- | --- |
| N1 | Log full page URLs with query strings containing tokens |
| N2 | Sell visitor profiles to third parties |
| N3 | Fingerprint across unrelated publishers without disclosure |
| N4 | Host malvertising — unapproved script tags (ADR 0013) |
| N5 | Fire non-essential tracking beacons **before** required consent (§3) |
| N6 | Store raw PII (email, precise location, long-term raw IP) in event tables |
| N7 | Use pre-ticked or dark-pattern consent UI |
| N8 | Share raw visitor identifiers with advertisers (aggregates only) |

---

## 8. Operator responsibility

Operators must publish their own privacy policy linking measurement practices,
obtain any required consent, configure retention, and respond to data-subject
requests. Serve provides configurable disclosure snippets and consent chrome in
all **six locales** (ADR 0011).

---

## 9. How this applies to every change

Any change touching event fields, beacons, consent, retention, exports, visitor
identifiers, or advertiser data sharing **MUST**:

1. Be reviewed against this document and
   [`../review/privacy-compliance.md`](../review/privacy-compliance.md).
2. State its privacy impact in the PR.
3. If it deviates, carry an ADR with privacy/legal sign-off (§0.4).

If unsure whether a change has privacy impact, **assume it does** and run the
checklist.

---

## Related

- [`measurement-spec.md`](./measurement-spec.md), [`scope-contract.md`](./scope-contract.md)
- [`billing-and-accounting-compliance.md`](./billing-and-accounting-compliance.md) (retention boundary)
- [ADR 0016](../adr/0016-self-hosted-data-controller-model.md), [ADR 0017](../adr/0017-consent-and-lawful-basis.md), ADR 0010, ADR 0013
- [`../review/privacy-compliance.md`](../review/privacy-compliance.md), [`../review/compliance.md`](../review/compliance.md)

Last updated: 2026-06-04
