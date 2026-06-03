# Privacy & Data Protection Self-Review

**Binding.** Use for **any** change touching event fields, beacons, consent,
retention, exports, visitor identifiers, or advertiser data sharing. If unsure
whether a change has privacy impact, **assume it does** and run this list.

Source of truth:
[`../explanation/privacy-and-ad-compliance.md`](../explanation/privacy-and-ad-compliance.md).
Do not delete items to pass. Mark `N/A` only when genuinely not applicable.

## Checklist

- [ ] Change reviewed against `privacy-and-ad-compliance.md`; privacy impact stated in the PR.
- [ ] Default collects the **minimum** needed; richer tracking is opt-in and consent-gated (privacy by default).
- [ ] Essential serving works **without** consent; non-essential beacons do **not** fire before required consent (ADR 0017).
- [ ] `measurement_enabled=false` serves the creative with **no** tracking beacon.
- [ ] Consent UI offers refusal as easily as acceptance; **no pre-ticked / dark-pattern** consent.
- [ ] Visitor identifiers **hashed/bucketed** (`visitor_bucket`); **no raw PII** (email, precise geo, long-term raw IP) in event tables.
- [ ] Page URLs stored **truncated**; no tokens/secrets in stored URLs (N1).
- [ ] Allowed origins enforced on every placement (ADR 0010).
- [ ] Advertisers receive **aggregated metrics only** — no raw visitor identifiers (N8).
- [ ] No third-party data sharing introduced by default; data stays on operator infra (ADR 0016).
- [ ] Retention: ordinary measurement uses the **privacy-first** (short, configurable) regime; billing-relevant data uses the **statutory** regime — the two are not conflated (privacy §6 / billing §7).
- [ ] Data-subject export/erasure honored; erasure is an additive tombstone; billing-relevant carve-out documented, not a silent count edit.
- [ ] Consent/retention settings changes are audited (P7).
- [ ] Six-locale disclosure/consent strings updated for any UI-visible change.
- [ ] Any deviation carries an **ADR with privacy/legal sign-off**.
