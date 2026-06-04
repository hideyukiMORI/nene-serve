# API contracts (OpenAPI 3.1)

These OpenAPI 3.1 documents are the **binding contract** for the three API
surfaces (ADR 0018). Per ADR 0018 the public serve document is kept **separate**
from the privileged surfaces; admin and service are split into their own
documents so each carries a single, clear auth model and audience.

| Document | Surface | Routes | Auth |
| --- | --- | --- | --- |
| [`public.openapi.json`](public.openapi.json) | Public serve | `GET /health`, `/public/*` | None (origin-gated, rate-limited, opaque tokens) |
| [`admin.openapi.json`](admin.openapi.json) | Operator console | `/admin/*` | Short-lived JWT bearer + RBAC Capability, tenant-scoped |
| [`service.openapi.json`](service.openapi.json) | Machine-to-machine | `/api/*` | Opaque scoped service token |

## Conventions

- **OpenAPI 3.1**, JSON. JSON payloads are `snake_case`.
- Errors are **RFC 9457 Problem Details** (`application/problem+json`); the
  `type` slug is registered in [`../explanation/terminology.md`](../explanation/terminology.md).
- `operationId`s follow the registry in terminology.md (§operationId stems) and
  must stay stable across OpenAPI, routes, and the MCP tool catalog.
- The **MCP tool catalog maps to `service.openapi.json` only** (ADR 0018,
  api-security §5) and is read-first: writes are expressed as *plans* requiring an
  explicit confirmation token, and are audited.

## Drift protection

`tests/Api/OpenApiContractTest.php` parses all three documents and asserts the
set of documented `{method, path}` pairs is **exactly** the set of routes the
`Kernel` registers — every documented operation is routed, and every route is
documented. A new endpoint, a renamed path, or an undocumented route fails the
test. Run it with the rest of the suite via `composer test`.

> These are hand-authored contracts; request/response schemas describe the shape
> the handlers produce but are not auto-generated. The route-coverage test is the
> guarantee that the path/method/auth surface cannot silently diverge from code.
