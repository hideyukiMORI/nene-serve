# Scope Contract — GOAL / DO / DON'T (binding)

**Status: binding (non-negotiable).** Charter for NeNe Serve.

Read first: [ADR 0009](../adr/0009-separate-from-contact-and-concierge.md),
[`measurement-spec.md`](./measurement-spec.md),
[`serve-embed-spec.md`](./serve-embed-spec.md),
[`privacy-and-ad-compliance.md`](./privacy-and-ad-compliance.md).

---

## GOAL

> **NeNe Serve lets a publisher run self-hosted ad slots on any site — weighted
> delivery, caps, fallback creatives, trustworthy impression and click metrics,
> and optional advertiser budgets settled via Invoice — without becoming a CRM,
> contact inbox, or full-stack ad exchange.**

Concretely:

1. Operator registers **creatives** and binds them to **placements**.
2. Publisher embeds **`serve.js`** (one line) on sites they control.
3. Visitors see ads per **weight**, **caps**, and **default fallback** rules.
4. Serve records **impressions** and **clicks** (redirect-based) for charts and CSV.
5. Operators (or AI via MCP) adjust **delivery plans** with audit trails.
6. Optional Phase 3+: **advertisers** fund campaigns; **Invoice** remains money SSOT.

---

## DO — Serve owns these

| # | Serve does |
| --- | --- |
| D1 | **Creative** assets: image, video URL/hosted file, sandboxed HTML5 bundle (ADR 0013) |
| D2 | **Placement** definition with public key for embed |
| D3 | **Delivery plan**: weighted rotation, per-creative/placement caps, schedule windows |
| D4 | **Default / fallback** creative when no eligible ad remains |
| D5 | **Impression** logging per [`measurement-spec.md`](./measurement-spec.md) |
| D6 | **Click** tracking via redirect endpoint |
| D7 | **Reporting**: time-series charts, CSV export, placement/creative breakdown |
| D8 | **Multi-tenant RBAC** (ADR 0006) |
| D9 | **Six-locale** admin UI and embed chrome (ADR 0011) |
| D10 | **OpenAPI** for admin, public serve, and `/api/*` automation |
| D11 | **MCP tools** on Serve HTTP only (read-first; audited writes) |
| D12 | **Advertiser / budget** entities (Phase 3+) with Invoice handoff for payments |

---

## DON'T — Serve must never do these

| # | Serve must NOT | Belongs to |
| --- | --- | --- |
| X1 | Host **contact form** submissions or operator inbox for inquiries | **NeNe Contact** |
| X2 | Run **chat scenarios** or conversational session graphs | **NeNe Concierge** |
| X3 | Issue **quotes, invoices, PDFs**, or record **payments** as SSOT | **NeNe Invoice** |
| X4 | **Match bank deposits** to receivables or send **collection reminders** | Other siblings (not Serve) |
| X5 | Import or normalize **bank CSV** | Other siblings (not Serve) |
| X6 | Operate a **global RTB/DSP** or third-party network bidding | Out of scope |
| X7 | Execute **unreviewed third-party ad tags** (raw `<script src=…>`) in MVP | Security (ADR 0013) |
| X8 | **Share a database** with siblings | HTTP only (ADR 0002) |
| X9 | Merge **form submissions** into impression tables | Separate event types |
| X10 | Auto-increase spend without caps when Invoice handoff is enabled | Operator + audit |

---

## Contact vs Concierge vs Serve

| | Contact | Concierge | Serve |
| --- | --- | --- | --- |
| Visitor outcome | Sends a message | Completes a guided flow | Views/clicks an ad |
| Core record | `submission` | `session` / `step` | `impression` / `click` |
| Embed | `embed.js` | scenario embed | `serve.js` |

Same page may load multiple scripts; **no shared DB**.

---

## Related

- [`../integrations/sibling-products.md`](../integrations/sibling-products.md)
- ADR 0011 (locales), ADR 0012 (measurement)

Last updated: 2026-06-03
