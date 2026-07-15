# ADR 0011: Six-Locale Application

## Status

accepted (amended 2026-07-15 — authority locale `en` → `ja`; see [Amendment 2026-07-15](#amendment-2026-07-15--authority-locale-en--ja))

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

> **Note (2026-07-15):** the authority locale is now `ja`, not `en` — see
> [Amendment 2026-07-15](#amendment-2026-07-15--authority-locale-en--ja).

### Fallback chain

`requested locale` → `placement.default_locale` → `organization.default_locale` → `en`

### CI (when runtime exists)

- ~~`composer locales:check` — every key in `en.json` exists in all five other files~~
  **Superseded by [Amendment 2026-07-15](#amendment-2026-07-15--authority-locale-en--ja): parity is measured against `ja.json`.**
- No empty translations for `error.*` and `consent.*` keys in production builds

### OpenAPI

- Problem Details `title` / `detail` for public API: English only (NENE2 convention)
- Admin may return localized validation messages via `Accept-Language` (Phase 2+)

## Consequences

**Benefits**

- One global product story; predictable for Suite operators.

**Costs**

- Six files to update per UI change; translators needed for non-English keys.

## Amendment 2026-07-15 — authority locale `en` → `ja`

**Status:** accepted. Amends the "Message catalogs" and "CI" sections above; the
original decision text is retained as the historical record.

### Context

The original decision made `en` the canonical key set ("every key in `en.json`
exists in all five other files"). Fleet-wide **Frontend Standard 04, I18N-8**
subsequently ruled the opposite and binding: the authority catalog is **`ja`
only**, and an `en`-authority implementation is MUST NOT. The stated basis is
that the authority catalog is the one the owner can adjudicate for correctness —
under `en` authority neither the owner nor CI can gate the quality of the
Japanese text. That standard names NeNe Serve as one of five migration targets
(payout / records / serve / suite / concierge) and explicitly classes it as a
**migration target, not a sanctioned deviation**.

This ADR and the standard were therefore in direct conflict. Owner ruling
(2026-07-15): **invert to `ja` authority.** The alternative — registering the
conflict as a sanctioned deviation — was rejected in favour of zero exceptions
between standard and implementation. The pilots for this inversion landed first
in payout (PR #162) and suite (PR #382).

### Decision

1. **Authority locale is `ja`.** Keys are authored in `ja` first; every other
   catalog — including `en` — mirrors that key set. Adding or removing a key in
   `ja` is what changes the key set.
2. **`en` remains the default locale and the runtime fallback.** Authority
   (which catalog owns the key set) and default/fallback (which catalog answers
   at runtime) are distinct concerns. The fallback chain below is unchanged, and
   `en` stays the default in the locale table above.
3. **Both catalog surfaces follow the same authority:**
   - TypeScript (`frontend/src/shared/i18n/messages/*.ts`) — `MessageKey =
     keyof typeof ja`, `MessageCatalog = Record<MessageKey, string>`. Parity is
     enforced at compile time for all six locales.
   - JSON (`locales/*.json`, backend + embed) — `LocaleCatalogs::CANONICAL =
     'ja'`; `composer locales:check` measures parity against `ja.json`.
4. **Six locales, unchanged.** This amendment does not add or remove a locale,
   so it is not a locale-schema change under the rule above.

### Consequences

- Non-Japanese catalogs, `en` included, are translations of `ja`. A key that
  exists only in `en` is now a compile error rather than the source of truth.
- The owner can adjudicate the authority catalog directly, which was the point
  of I18N-8.
- Engineering docs stay English (ADR 0008); only the *authority of the message
  key set* moved, not the documentation language.

## Related

- [`../development/i18n.md`](../development/i18n.md)
- [`../../locales/README.md`](../../locales/README.md)
- Frontend Standard 04 (i18n) — I18N-8 (ja authority), I18N-9 (non-authority catalogs)
- Fleet pilots: nene-payout PR #162, nene-suite PR #382
