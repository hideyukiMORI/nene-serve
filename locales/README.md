# Locale message catalogs

NeNe Serve ships **six application locales** (ADR 0011). Engineering docs in `docs/` stay English; all operator-facing and embed-facing UI strings live here.

| File | Locale | BCP 47 |
| --- | --- | --- |
| `en.json` | English | `en` (default locale, runtime fallback) |
| `ja.json` | Japanese | `ja` (**authority — canonical key set**) |
| `zh-Hans.json` | Chinese (Simplified) | `zh-Hans` |
| `ko.json` | Korean | `ko` |
| `de.json` | German | `de` |
| `es.json` | Spanish | `es` |

Keys are authored in the authority catalog `ja.json` first (ADR 0011 Amendment 2026-07-15; Frontend Standard 04, I18N-8); every other catalog, `en.json` included, mirrors that key set. `en` stays the default/fallback locale at runtime.

Phase 0: catalogs are placeholders until runtime. CI requires key parity across all six files against `ja.json`.

See [`docs/development/i18n.md`](../docs/development/i18n.md).
