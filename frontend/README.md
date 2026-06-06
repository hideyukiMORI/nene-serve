# NeNe Serve — Frontend

React + TypeScript admin SPA for NeNe Serve (placements, creatives & review, metrics,
marketplace/billing — built out incrementally).

Policy: this app follows the shared NeNe frontend conventions used by the sibling repos
(`../nene-deal`, `../nene-invoice`, `../nene-records`) — layered `app → pages → features →
entities → shared` (Feature-Sliced), enforced import zones, theme-token-only styling,
Storybook contracts, MSW-backed tests, `openapi-typescript` codegen.

**Languages:** six application locales (ADR 0011) — `en` (source of truth, ADR 0008), `ja`,
`zh-Hans`, `ko`, `de`, `es`. Keep in parity with the server-side catalogs in `../locales/*.json`.

## Commands

```bash
npm install
npm run dev          # http://localhost:5180 (API proxied to the PHP app on APP_PORT, default 8010)
npm run mock         # dev server backed by MSW handlers (no PHP needed)
npm run storybook    # component catalog on :6107
npm run codegen      # regenerate src/shared/api/schema.gen.ts from ../docs/api/admin.openapi.json
npm run check        # type-check, lint, format, test, knip, build-storybook
```

The client calls the three serve surfaces under `/admin/*` (this console), `/api/*` and
`/public/*` (matching the OpenAPI contracts in `../docs/api/`). Optional `.env.local`:

```dotenv
VITE_API_BASE_URL=        # empty = same-origin via the dev proxy
VITE_ORG_SLUG=            # sent as X-Organization-Slug (omit for single-tenant)
VITE_API_KEY=             # sent as X-NENE2-API-Key on writes (omit when the API is open)
VITE_REQUIRE_LOGIN=       # 'true' gates the app behind /login (set when the backend enforces JWT)
```

Operator login: `/login` (`POST /admin/login`). The bearer token is held in memory only (lost
on reload — log in again); a future ADR may move to an httpOnly cookie.

## Structure

- `src/app/` — providers, router, root error boundary, fail-closed auth gate, app shell
- `src/pages/` — route wiring
- `src/features/` — user workflows (hooks + presentational UI)
- `src/entities/` — API resource slices (DTO ↔ model, query keys, TanStack hooks)
- `src/shared/` — `api` (transport + RFC 9457 errors), `i18n` (six locales), `ui` (theme tokens +
  primitives), `auth`, `config`, `lib`

Theme swap: edit the `src/shared/ui/theme/active.css` import only.
