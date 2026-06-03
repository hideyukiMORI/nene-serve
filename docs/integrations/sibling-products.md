# Sibling Product Integration

HTTP only — ADR 0002. **No NeNe Clear or bank/reconciliation integrations.**

## Direction map

```
NeNe Serve  →  HTTP  →  NeNe Invoice   (advertiser billing / payment status — Phase 3+)
NeNe Serve  →  HTTP  →  NeNe Deal      (optional opportunity from campaign — Phase 4+)
NeNe Serve  →  HTTP  →  NeNe Records   (read asset metadata for creatives — Phase 4+)
NeNe Concierge  →  HTTP  →  NeNe Serve (optional conversion beacon — Phase 4+)
```

**No default integration** with NeNe Contact (parallel embed only).

## Planned

| Sibling | Direction | Use case | Phase |
| --- | --- | --- | --- |
| **Invoice** | Serve → Invoice | Charge advertiser, record payment against `budget_cents` | 3+ |
| **Deal** | Serve → Deal | High-value placement lead → opportunity | 4+ |
| **Records** | Serve → Records (read) | Product image URLs for creative assembly | 4+ |
| **Concierge** | Concierge → Serve | Log attributed conversion event | 4+ |
| **Contact** | — | Coexist on site; no shared DB | — |

## Environment variables (planned)

| Variable | Purpose |
| --- | --- |
| `NENE_INVOICE_API_BASE_URL` | Invoice `/api/*` |
| `NENE_INVOICE_SERVICE_TOKEN` | Scoped machine token |
| `NENE_DEAL_API_BASE_URL` | Optional |
| `NENE_RECORDS_API_BASE_URL` | Read-only |

## Rules

- `src/Upstream/` HTTP clients; interface in UseCases
- Handoff idempotent via `external_reference`
- Failed Invoice handoff does not pause serving unless operator enables `pause_on_budget_exhausted`

## Related

- [`invoice-advertiser-handoff-contract.md`](./invoice-advertiser-handoff-contract.md)

Last updated: 2026-06-03
