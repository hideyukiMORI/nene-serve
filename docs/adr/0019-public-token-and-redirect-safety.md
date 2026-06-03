# ADR 0019: Public Token & Redirect Safety

## Status

accepted

## Context

The public serve API issues tokens (`impression_token`, `click_token`) and
performs a **redirect** to an advertiser destination on click. These are the
parts of the system an attacker touches most directly. Two classic abuses must be
designed out: **open redirect** (turning the click endpoint into a phishing relay)
and **count inflation** (replaying tokens to fake impressions/clicks — which, in
marketplace mode, is fraud against billing). This ADR makes the token and
redirect rules binding, refining ADR 0010 §3–§4.

## Decision

1. **Opaque, unguessable tokens.** `impression_token` and `click_token` are
   random/opaque; they do not encode internal numeric ids.
2. **Click token lifetime.** `click_token` is **single-use or short TTL**
   (configurable, **default 15 minutes**). Expired/used tokens return `404`/`410`
   — **never** a fallback redirect.
3. **No open redirect.** The redirect target is the creative's **registered**
   `destination_url`. It **MUST** be `https` (or `http` only on localhost dev) and
   **MUST** match a value stored on the creative. The client never supplies the
   target.
4. **Idempotent impression beacon.** Replaying an `impression_token` does **not**
   inflate counts — required for billing integrity (ADR 0015).
5. **Rate-limiting & abuse.** Serve/beacon/click endpoints are rate-limited per
   IP (and per placement). Blocked requests return `429`; a blocked serve records
   a **reason code** and never silently drops a metric (ADR 0010 §6).
6. **No token leakage.** Tokens are not stored in URLs/logs alongside other query
   secrets (privacy N1); not reused across placements.
7. **Deviation gate:** changing token lifetime, redirect validation, or idempotency
   requires an ADR with security sign-off.

## Consequences

**Benefits**

- Click endpoint cannot be weaponized as an open redirect.
- Token replay cannot inflate metrics or advertiser charges.

**Costs**

- Requires token storage/expiry plumbing and idempotency keys on beacons.

## Related

- [`../explanation/api-security-spec.md`](../explanation/api-security-spec.md) (binding) §3
- [ADR 0018](0018-api-surface-and-authentication-model.md), ADR 0010, ADR 0012
- [`../explanation/measurement-spec.md`](../explanation/measurement-spec.md), [`billing-and-accounting-compliance.md`](../explanation/billing-and-accounting-compliance.md) (ADR 0015 integrity)
