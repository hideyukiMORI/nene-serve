# ADR 0010: Public Serve API Security

## Status

accepted

## Decision

1. **Allowed origins** per placement (serve + beacon + redirect).
2. **Rate limits** on serve, impression, and click endpoints.
3. **Click tokens** single-use or short TTL (configurable, default 15 minutes).
4. **No open redirect** — `destination_url` must be https (or http on localhost dev) and registered on creative.
5. **CORS** never `*` in production for authenticated admin; public endpoints reflect allowlist only.
6. **Bot filtering** optional hook (Phase 2) — must not drop metrics silently without reason code.

## Related

- [`../explanation/serve-embed-spec.md`](../explanation/serve-embed-spec.md)
