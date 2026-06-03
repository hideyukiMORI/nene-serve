# ADR 0018: API Surface & Authentication Model

## Status

accepted

## Context

NeNe Serve exposes HTTP to three very different callers: anonymous browsers
(via `serve.js`), authenticated operators, and machines (MCP / automation /
sibling handoff direction). Mixing their trust levels — e.g. an admin field
leaking onto a public response, or a public endpoint accepting a privileged
parameter — is a classic ad-tech security failure. The surfaces must be
separated **by design**.

ADR 0006 already fixes multi-tenant RBAC; ADR 0010 fixes public serve security.
This ADR fixes the **overall surface map and auth model** that ties them together.

## Decision

1. **Three surfaces, three trust levels:**
   - **Public serve** `/public/*` — **no auth**; origin-gated, rate-limited,
     opaque tokens only; no secrets/PII in requests or responses.
   - **Admin** `/admin/*` — **JWT + `Capability`** (ADR 0006); tenant-scoped;
     mutations audited; CORS never `*` in production.
   - **Service** `/api/*` — **scoped service tokens** (not human capabilities);
     read-first; audited writes; `insufficient-scope` → 403.
   - `GET /health` — unauthenticated liveness only, no data.
2. **OpenAPI 3.1 is the contract** for every surface; public serve is a separate
   OpenAPI document from admin/service. Errors use **RFC 9457 Problem Details**;
   JSON is snake_case.
3. **Fail closed.** Missing/invalid auth, unknown origin, or wrong scope denies;
   never degrades to a more permissive path.
4. **Tenant isolation is absolute** (ADR 0006): every authenticated query scoped
   by resolved `organization_id`; only superadmin is cross-tenant.
5. **MCP maps to the Serve OpenAPI only**, read-first, audited writes.
6. **Deviation gate:** changing a surface's auth model or exposure requires an ADR
   with security sign-off (`../explanation/api-security-spec.md` §0).

## Consequences

**Benefits**

- A reviewer can reason about each surface independently; no privileged data on
  public paths.
- Clear place for every endpoint; predictable for SDK/MCP consumers.

**Costs**

- Two+ OpenAPI documents to maintain; discipline needed to keep public responses
  minimal.

## Related

- [`../explanation/api-security-spec.md`](../explanation/api-security-spec.md) (binding)
- [ADR 0019](0019-public-token-and-redirect-safety.md), ADR 0010, ADR 0006
- [`../explanation/serve-embed-spec.md`](../explanation/serve-embed-spec.md)
