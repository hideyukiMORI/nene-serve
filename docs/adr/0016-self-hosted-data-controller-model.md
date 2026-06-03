# ADR 0016: Self-Hosted Data-Controller Model & Privacy-by-Design Boundary

## Status

accepted

## Context

NeNe Serve measures ad impressions and clicks, which can involve personal data
(identifiers, IP-adjacent signals, page context). It deploys across six locales,
spanning **GDPR/ePrivacy** (EU) and **個人情報保護法** (Japan) regimes. We must
fix, up front, **who is responsible for personal data** and **where it lives**,
so the product is defensible to a privacy reviewer.

The product philosophy is already "publisher-first — metrics and inventory stay
on the operator's infrastructure." This ADR makes that a binding privacy boundary.

## Decision

1. **The operator (publisher) is the data controller.** They decide purposes,
   publish the privacy policy, obtain consent, and answer data-subject requests.
2. **NeNe Serve is self-hosted software run by the controller.** There is **no
   NeNe-operated data collection** and **no third-party data sharing by default**.
   Visitor data stays on the operator's infrastructure.
3. **Serve is jurisdiction-neutral.** It does not encode one country's law; it
   ships the **controls** (consent gating, minimization, retention limits,
   data-subject-request tooling) operators use to comply with their regime.
4. **Privacy by design and by default.** The default config collects the minimum
   needed to serve and measure; richer tracking is opt-in and consent-gated
   (ADR 0017).
5. **Advertisers (marketplace) receive aggregated metrics only** — never raw
   visitor identifiers.
6. **Optional sibling HTTP** (Invoice/Records/…) stays within operator-controlled
   endpoints (ADR 0002); the Invoice handoff carries billing identity/aggregates,
   not visitor tracking data.
7. **Deviation gate:** any change that shares data more widely, adds a default
   collection, or weakens minimization requires an ADR with privacy/legal
   sign-off, per `../explanation/privacy-and-ad-compliance.md` §0.

## Consequences

**Benefits**

- Clear, defensible accountability: one controller, data on their own infra.
- No cross-border transfer by default; smaller legal surface.
- Matches the self-hosted, publisher-first product story.

**Costs**

- Operators carry real controller duties; Serve must make compliance **easy**
  (disclosure snippets, consent chrome, DSR tooling) rather than assume it.

## Related

- [`../explanation/privacy-and-ad-compliance.md`](../explanation/privacy-and-ad-compliance.md) (binding)
- [ADR 0017](0017-consent-and-lawful-basis.md), ADR 0002, ADR 0010, ADR 0011
- [`../explanation/philosophy.md`](../explanation/philosophy.md)
