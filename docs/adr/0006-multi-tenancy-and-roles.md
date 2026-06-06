# ADR 0006: Multi-Tenancy and Roles (Foundational)

## Status

accepted

## Decision

Multi-tenant from foundation (NeNe Records pattern):

- `organization_id` on `placements`, `creatives`, `campaigns`, `impressions`, `clicks`, `advertisers`, `audit_events`, `users`
- Middleware resolves org before authz
- Public `serve.js` resolves org via `public_placement_key`
- Roles: superadmin, organization admin, editor, analyst (read-only metrics)

## Tenant resolution modes (admin surface)

How the **admin** surface determines the current tenant is configurable via
`TENANT_RESOLUTION` (NeNe Records pattern). The public surface always resolves the
org from the `public_placement_key`, and the service surface from the scoped
token; these are unaffected by the mode.

| Mode | Source | Use |
| --- | --- | --- |
| `login` (default) | org slug typed at sign-in, carried in the JWT `org` claim | single-console / current behaviour |
| `single` | fixed `TENANT_ORG_SLUG` | one org owns the whole install |
| `subdomain` | `acme.<TENANT_BASE_DOMAIN>` → `acme` | SaaS-style per-tenant subdomains |
| `path` | `/acme/admin/...` → `acme` (prefix stripped before routing) | shared host without wildcard DNS |
| `custom_domain` | tenant's own domain → `organizations.custom_domain` | tenants bring their own domain |

In the URL modes, `Tenant\Resolution\OrgResolverMiddleware` resolves the tenant
before auth and fails closed on the admin surface (unknown → 404, inactive → 403);
`AdminAuthMiddleware` then **reconciles the JWT against the resolved tenant** —
a token minted for another org is refused (403), except a cross-tenant superadmin
acting within the resolved org. In `login` mode no tenant is taken from the URL
and the JWT is authoritative, exactly as before.

## Related

- [`../explanation/domain-model.md`](../explanation/domain-model.md)
