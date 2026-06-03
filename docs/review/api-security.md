# API & serve.js Security Self-Review

**Binding.** Use for **any** change touching an HTTP endpoint, auth, CORS, tokens,
redirects, rate limits, or error shape. If unsure whether a change has security
impact, **assume it does** and run this list.

Source of truth:
[`../explanation/api-security-spec.md`](../explanation/api-security-spec.md).
Do not delete items to pass. Mark `N/A` only when genuinely not applicable.

## Checklist

- [ ] Change reviewed against `api-security-spec.md`; security impact stated in the PR.
- [ ] Endpoint lives on the correct surface (`/public/*` none, `/admin/*` JWT+Capability, `/api/*` scoped token) — trust levels not blurred (ADR 0018).
- [ ] Public responses carry **no secrets, no PII, no internal ids** beyond opaque tokens.
- [ ] **Allowed origins** enforced on serve/beacon/redirect; **CORS never `*`** for credentialed routes in production.
- [ ] **Rate limits** on serve/beacon/click; blocked requests return `429` and record a **reason code** (no silent metric drop).
- [ ] `click_token` opaque, single-use or short TTL (default 15 min); expired/used → `404`/`410`, never a fallback redirect (ADR 0019).
- [ ] **No open redirect**: `destination_url` is `https` (localhost `http` only), registered on the creative, never client-supplied.
- [ ] `impression_token` beacon is **idempotent** (replay does not inflate counts).
- [ ] Admin/service queries **scoped by `organization_id`**; cross-tenant access impossible except superadmin (ADR 0006).
- [ ] Mutations audited (delivery plan, creative publish, budget/pricing, consent/retention).
- [ ] Service tokens grant explicit scopes; `insufficient-scope` → 403; MCP read-first, audited writes, Serve OpenAPI only.
- [ ] HTML5 creatives render only in sandboxed iframe + strict CSP; no `eval` of responses (ADR 0013).
- [ ] Errors use **Problem Details**; **no stack traces** / internal details in production.
- [ ] Secrets in `.env` only; tokens/keys never logged; TLS for non-localhost.
- [ ] Input validated against OpenAPI schema; unknown fields rejected.
- [ ] New Problem Details slugs / `operationId`s registered in `terminology.md`.
- [ ] Any deviation carries an **ADR with security sign-off**.
