# API & serve.js Security — Binding Rules

**Status: binding (non-negotiable).** Source of truth for how NeNe Serve's HTTP
surfaces behave under attack and abuse. A security reviewer must be able to find
**zero deviations** from the rules below. Deepens
[ADR 0010](../adr/0010-public-serve-api-security.md).

These are **MUST** requirements. Where a rule here conflicts with UX,
performance, or convenience, **security wins**.

Read first: [`serve-embed-spec.md`](./serve-embed-spec.md),
[`measurement-spec.md`](./measurement-spec.md),
[`privacy-and-ad-compliance.md`](./privacy-and-ad-compliance.md),
[ADR 0018](../adr/0018-api-surface-and-authentication-model.md),
[ADR 0019](../adr/0019-public-token-and-redirect-safety.md), ADR 0010, ADR 0006,
self-review [`../review/api-security.md`](../review/api-security.md).

---

## 0. Governing principles

1. **Three surfaces, three trust levels.** Public serve API (untrusted callers),
   admin API (authenticated humans), service API (scoped machines). Each has its
   own auth and exposure rules; they are **never** blurred (ADR 0018).
2. **Least exposure.** Public responses carry the minimum needed to render and
   measure — **no secrets, no PII, no internal ids beyond opaque tokens**.
3. **Fail closed.** Missing/invalid auth, unknown origin, expired token, or
   exhausted rate limit **denies**; it never falls back to a more permissive path.
4. **Tenant isolation is absolute.** Every authenticated query is scoped by the
   resolved `organization_id`; cross-tenant access is impossible except for
   superadmin (ADR 0006).
5. **No silent deviation.** Any departure requires an ADR with security sign-off.

---

## 1. The three API surfaces

| Surface | Path | Caller | Auth | Notes |
| --- | --- | --- | --- | --- |
| **Public serve** | `/public/*` | Browsers via `serve.js` | **None** | Origin-gated, rate-limited, opaque tokens only |
| **Admin** | `/admin/*` | Operators (humans) | **JWT + `Capability`** | Tenant-scoped (ADR 0006); mutations audited |
| **Service** | `/api/*` | Machines / MCP / automation | **Scoped service token** | Read-first; audited writes; per-scope grants |
| System | `GET /health` | Anyone | None | Liveness only; no data |

OpenAPI 3.1 is the **contract** for all three; errors use **RFC 9457 Problem
Details**. JSON is snake_case. The public serve doc and admin/service docs are
separate OpenAPI documents.

---

## 2. Public serve API (untrusted)

Endpoints (per [`serve-embed-spec.md`](./serve-embed-spec.md)):

- `GET /public/placements/{public_placement_key}/serve`
- `POST /public/events/impression`
- `GET /public/clicks/{click_token}` (302 redirect)

Rules:

- **Allowed origins** enforced on serve, beacon, and redirect — the placement's
  `allowed_origins` allowlist. **CORS reflects the allowlist only; never `*`**.
- **Rate limits** per IP (and per placement) on all three endpoints. Exceeding a
  limit returns `429` and, if a serve is blocked, records a **reason code** — it
  **MUST NOT** silently drop a metric (privacy/measurement integrity).
- **No authentication**, therefore **no credentials, secrets, or PII** in any
  request or response. The `public_placement_key` is a public identifier, not a
  secret.
- Responses expose **opaque tokens** (`impression_token`, `click_token`) and the
  creative payload only — never internal numeric ids, advertiser data, or other
  tenants' data.
- **No `eval`** of API responses on the client; HTML5 creatives render only in a
  **sandboxed iframe** with strict CSP (ADR 0013). Single script entry point
  (`serve.js`) keeps the embed CSP-friendly.

---

## 3. Tokens & redirect safety (ADR 0019)

- **`click_token`**: opaque, unguessable, **single-use or short TTL** (configurable,
  **default 15 minutes**). Expired/used tokens return `404`/`410`, never a
  fallback redirect.
- **No open redirect.** The redirect target is the creative's **registered**
  `destination_url`; it **MUST** be `https` (or `http` only on localhost dev) and
  **MUST** match a value stored on the creative. The client never supplies the
  redirect URL.
- **`impression_token`**: idempotent — replaying the same token does not inflate
  counts (billing integrity, ADR 0015).
- Tokens are **not** carried in stored URLs/logs with other query secrets
  (privacy N1).

---

## 4. Admin API (authenticated humans)

- **JWT** required for all mutating routes; `Capability` enforced per route
  (ADR 0006). Roles: superadmin, organization admin, editor, analyst (read-only).
- **CORS never `*`** for credentialed admin endpoints in production.
- Every query **scoped by resolved `organization_id`**; cross-tenant read/write
  is prohibited (only superadmin operates cross-tenant).
- Mutations (delivery plan, creative publish, budget/pricing, consent/retention
  settings) are **audited** (who/when/what).

---

## 5. Service API & MCP (scoped machines)

- **Scoped service tokens** (not human capabilities); each token grants explicit
  scopes (e.g. `read:metrics`, `write:delivery_plan`).
- **MCP is read-first**; writes are **audited** and may require a confirmation
  token (delivery-plan changes). MCP maps to the Serve OpenAPI **only**.
- Aggregated metrics by default; raw IP/visitor identifiers excluded unless an
  admin tool sets `include_sensitive=true` (audit logged) — see measurement-spec.
- `insufficient-scope` (403) when a token lacks the required scope.

---

## 6. Cross-cutting

- **Problem Details** for all errors; **no stack traces** or internal details in
  production responses.
- **Secrets in `.env` only**, never committed; tokens/keys never logged.
- **TLS** for all non-localhost traffic; secure cookie/storage flags where used.
- **Bot / invalid-traffic filtering** (Phase 2 hook) must record a reason code,
  never drop metrics silently (ADR 0010 §6, billing integrity ADR 0015).
- Input is validated against the OpenAPI schema; unknown fields rejected.

---

## 7. How this applies to every change

Any change touching an HTTP endpoint, auth, CORS, tokens, redirects, rate limits,
or error shape **MUST**:

1. Be reviewed against this document and
   [`../review/api-security.md`](../review/api-security.md).
2. State its security impact in the PR.
3. If it deviates, carry an ADR with security sign-off.

If unsure whether a change has security impact, **assume it does** and run the
checklist.

---

## Related

- [`serve-embed-spec.md`](./serve-embed-spec.md), [`measurement-spec.md`](./measurement-spec.md)
- [ADR 0018](../adr/0018-api-surface-and-authentication-model.md), [ADR 0019](../adr/0019-public-token-and-redirect-safety.md), ADR 0010, ADR 0006, ADR 0013
- [`privacy-and-ad-compliance.md`](./privacy-and-ad-compliance.md), [`billing-and-accounting-compliance.md`](./billing-and-accounting-compliance.md)
- [`../review/api-security.md`](../review/api-security.md)

Last updated: 2026-06-04
