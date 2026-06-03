# Backend Standards (draft)

## HTTP surfaces

| Prefix | Use |
| --- | --- |
| `/admin/*` | Operator JWT |
| `/public/*` | serve.js, beacon, click redirect |
| `/api/*` | Service token (MCP automation, Concierge) |

## Events

Append-only `impressions` and `clicks` tables; aggregate rollups via nightly job or materialized view (Phase 2).

## i18n

Load messages via `LocaleResolver` from `locales/{code}.json` — never branch on locale in UseCase business rules.

Last updated: 2026-06-03
