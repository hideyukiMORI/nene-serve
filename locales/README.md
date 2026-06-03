# Locale message catalogs

NeNe Serve ships **six application locales** (ADR 0011). Engineering docs in `docs/` stay English; all operator-facing and embed-facing UI strings live here.

| File | Locale | BCP 47 |
| --- | --- | --- |
| `en.json` | English | `en` (default) |
| `ja.json` | Japanese | `ja` |
| `zh-Hans.json` | Chinese (Simplified) | `zh-Hans` |
| `ko.json` | Korean | `ko` |
| `de.json` | German | `de` |
| `es.json` | Spanish | `es` |

Phase 0: catalogs are placeholders until runtime. CI will require key parity across all six files once `en.json` is populated.

See [`docs/development/i18n.md`](../docs/development/i18n.md).
