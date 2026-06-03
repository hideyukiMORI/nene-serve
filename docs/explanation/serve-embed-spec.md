# serve.js Embed Spec (binding)

Contract between NeNe Serve and the publisher's site.

---

## Snippet

```html
<script
  src="https://{serve-host}/serve.js"
  data-placement="{public_placement_key}"
  data-locale="en"
  async
></script>
```

| Attribute | Required | Values |
| --- | --- | --- |
| `data-placement` | yes | Public placement key |
| `data-locale` | no | One of ADR 0011 locales (`en`, `ja`, `zh-Hans`, `ko`, `de`, `es`) |
| `data-width` / `data-height` | no | Hint for layout (placement may override) |

---

## Client flow

1. Load `serve.js` (versioned filename in production).
2. `GET /public/placements/{key}/serve` with `Origin` check.
3. Render creative (image, video element, or sandboxed iframe for HTML5).
4. Beacon impression with returned `impression_token`.
5. Wrap click targets through `/public/clicks/{click_token}`.

---

## Server obligations

- Enforce **allowed_origins** on placement.
- Apply **delivery plan** (weights, caps, schedule, default creative).
- Rate-limit serve and beacon endpoints per IP.
- Return localized error/fallback strings from `locales/` when embed UI shows messages.

---

## Security

- No `eval` of API responses.
- HTML5 creatives only from **approved bundles** (ADR 0013), rendered in a
  sandboxed iframe with strict CSP.
- CSP-friendly: single script entry point.
- Origin-gated, rate-limited public endpoints; opaque tokens only; **no open
  redirect** and short-lived `click_token` — full rules in
  [`api-security-spec.md`](./api-security-spec.md) (binding), ADR 0018, ADR 0019.

---

## Related

- [`api-security-spec.md`](./api-security-spec.md) (binding), ADR 0010, ADR 0013, ADR 0018, ADR 0019
- [`measurement-spec.md`](./measurement-spec.md)

Last updated: 2026-06-04
