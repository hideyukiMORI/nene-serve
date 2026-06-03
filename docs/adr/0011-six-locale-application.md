# ADR 0011: Six-Locale Application

## Status

accepted

## Context

NeNe Serve targets publishers and operators in multiple markets. Bilingual (ja/en) alone is insufficient. Repository engineering docs remain English (ADR 0008).

## Decision

### Supported application locales (exactly six)

| Code | Language |
| --- | --- |
| `en` | English (default) |
| `ja` | Japanese |
| `zh-Hans` | Chinese (Simplified) |
| `ko` | Korean |
| `de` | German |
| `es` | Spanish |

Adding or removing a locale requires an ADR amendment and a major version bump of locale schema.

### Message catalogs

- All operator UI strings: `locales/{code}.json`
- Embed chrome (loading, consent, errors): same catalogs via `data-locale` on `serve.js`
- **Forbidden:** hard-coded user-visible strings in PHP/TSX outside tests

### Fallback chain

`requested locale` → `placement.default_locale` → `organization.default_locale` → `en`

### CI (when runtime exists)

- `composer locales:check` — every key in `en.json` exists in all five other files
- No empty translations for `error.*` and `consent.*` keys in production builds

### OpenAPI

- Problem Details `title` / `detail` for public API: English only (NENE2 convention)
- Admin may return localized validation messages via `Accept-Language` (Phase 2+)

## Consequences

**Benefits**

- One global product story; predictable for Suite operators.

**Costs**

- Six files to update per UI change; translators needed for non-English keys.

## Related

- [`../development/i18n.md`](../development/i18n.md)
- [`../../locales/README.md`](../../locales/README.md)
