# ADR 0017: Consent and Lawful Basis for Measurement

## Status

accepted

## Context

Under GDPR/ePrivacy and similar regimes, **non-essential** storage and tracking
identifiers generally require **consent before** they are set, while strictly
necessary functionality does not. NeNe Serve must separate "serve the ad" from
"track the visitor" so operators can run compliant defaults.

## Decision

1. **Essential serving needs no consent.** Returning and rendering a creative
   (the requested functionality) works without tracking consent.
2. **Non-essential measurement is consent-gated.** Cross-visit identifiers,
   frequency-cap `visitor_bucket`s, and any persistent client storage are
   non-essential. Their beacons **MUST NOT** fire before a positive consent
   signal where the regime requires consent.
3. **Opt-out switch.** `measurement_enabled=false` on a placement serves the
   creative **without** any tracking beacon.
4. **Recorded basis.** The lawful basis / consent mode in effect is recorded with
   configuration; it is not inferred per request.
5. **No dark patterns.** Six-locale consent chrome must make refusal as easy as
   acceptance; **pre-ticked consent is prohibited**.
6. **Data subject rights.** Access/export and erasure are supported (tooling
   Phase 2+). Erasure is an additive tombstone; where data is also
   billing-relevant, the minimal statutory substantiation is retained under a
   documented carve-out (privacy doc §5, billing doc §7) — never a silent count
   edit.
7. **Deviation gate:** weakening consent gating or expanding default tracking
   requires an ADR with privacy/legal sign-off.

## Consequences

**Benefits**

- Compliant-by-default behavior; serving never blocked by consent, tracking never
  runs without it where required.
- Clean interaction with billing retention (no conflict between erasure and
  statutory substantiation).

**Costs**

- Requires consent-state plumbing through the serve/beacon path and six-locale
  consent UI before non-essential measurement ships.

## Related

- [`../explanation/privacy-and-ad-compliance.md`](../explanation/privacy-and-ad-compliance.md) (binding) §3, §5
- [`../explanation/measurement-spec.md`](../explanation/measurement-spec.md)
- [ADR 0016](0016-self-hosted-data-controller-model.md), ADR 0011 (locales), ADR 0012 (imp/click)
- [`../explanation/billing-and-accounting-compliance.md`](../explanation/billing-and-accounting-compliance.md) §7 (retention boundary)
