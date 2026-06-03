# ADR 0002: Separate Product from Sibling NeNe Applications

## Status

accepted

## Decision

- Independent repo and database.
- `NeNe Serve → sibling HTTP` for optional handoff; never embed sibling code.
- MCP tools = Serve OpenAPI only.
- No shared schema with Contact, Concierge, Invoice, Records, Vault, Deal, or any back-office product.

Money for advertiser spend: **Invoice SSOT** when marketplace mode ships (Phase 3+).

## Related

- [`../integrations/sibling-products.md`](../integrations/sibling-products.md)
