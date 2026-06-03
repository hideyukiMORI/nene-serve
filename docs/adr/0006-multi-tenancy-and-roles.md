# ADR 0006: Multi-Tenancy and Roles (Foundational)

## Status

accepted

## Decision

Multi-tenant from foundation (NeNe Records pattern):

- `organization_id` on `placements`, `creatives`, `campaigns`, `impressions`, `clicks`, `advertisers`, `audit_events`, `users`
- Middleware resolves org before authz
- Public `serve.js` resolves org via `public_placement_key`
- Roles: superadmin, organization admin, editor, analyst (read-only metrics)

## Related

- [`../explanation/domain-model.md`](../explanation/domain-model.md)
