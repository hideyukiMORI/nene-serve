# Admin console — help & reference

What every screen and field in the NeNe Serve operator console means. This is a
**reference** (look things up); for a guided walk-through see the
[tutorial](../tutorial/first-campaign.md).

The console is the `/admin/*` surface (a React SPA). It is multi-tenant: you
always act inside **one organization**, and every list is scoped to your tenant.
The UI is available in six languages (English, 日本語, 简体中文, 한국어, Deutsch,
Español) — switch with the globe toggle in the top bar.

> Local dev URLs (default): console `http://localhost:5180`, API
> `http://localhost:8010`, Mailpit (caught email) `http://localhost:8013`.

---

## Roles & what they can do

Your role is fixed on your account and decides which actions you can take
(capabilities). The console hides or refuses actions you lack.

| Role | Typical use | Can manage placements/creatives | Can review/approve | Can manage marketplace/billing | Can manage users/settings |
| --- | --- | --- | --- | --- | --- |
| `superadmin` | Platform operator (cross-tenant) | ✔ | ✔ | ✔ | ✔ |
| `org_admin` | Tenant administrator | ✔ | ✔ | ✔ | ✔ |
| `editor` | Builds placements & creatives, submits for review | ✔ | submit only | — | — |
| `analyst` | Reads metrics | — | — | — | — |

**Four-eyes rule:** the person who *submits* a creative for review may **not**
approve their own submission. Approval needs a second reviewer.

---

## Top bar

- **Brand / product name** — returns to Placements.
- **Primary nav** — Placements · Creatives · Review · Metrics · Marketplace ·
  Users · Settings (you only see what your role allows).
- **Theme toggle** (sun/moon) — light or dark.
- **Language toggle** (globe) — switches UI language; remembered per browser.
- **Sign out** — clears your session (the bearer token is held in memory only,
  so closing the tab also signs you out).

---

## Login (`/login`)

Exchange credentials for a short-lived session.

| Field | Meaning |
| --- | --- |
| **Organization** | Your tenant slug (e.g. `acme`). Required because one email may exist in different tenants. |
| **Email** | Your account email. |
| **Password** | Your password. |

Wrong organization/email/password returns a generic "invalid email or password"
(no hint about which was wrong).

## Set password (`/set-password?token=…`)

The page an invited user lands on from the invitation email. It validates the
single-use token, shows the invited email, and lets the person choose a password
(min 8 characters). The link expires after 72 hours and works once.

---

## Placements (`/`)

An **ad slot** on a publisher page. serve.js resolves a placement to an approved
creative at request time.

**List columns**

| Column | Meaning |
| --- | --- |
| **Key** | The public placement key — the non-secret id you put in the serve.js snippet (`data-placement`). |
| **Status** | `active` (can serve) or `archived` (tombstoned, never served; never deleted). |
| **Default creative** | The creative served when no other delivery rule applies (`—` if unset). |

**New placement form**

| Field | Meaning |
| --- | --- |
| **Public placement key** | The id used in the embed snippet. Choose something stable, e.g. `news_home_top`. |
| **Allowed origins (comma-separated)** | Sites permitted to embed this placement (CORS allowlist). Empty = no origin restriction. Never `*` for credentialed use. |
| **Default creative id (optional)** | Pre-bind an approved creative as the default. |

---

## Creatives (`/creatives`)

The **ad content**. Only an **approved** creative is ever served (ADR 0020/0021).
Creatives are versioned and immutable once created; you revise into a new version.

**List columns**

| Column | Meaning |
| --- | --- |
| **ID** | Creative identifier. |
| **Type** | `image`, `video`, or `html5_bundle`. |
| **Review status** | `draft` → `submitted` → `in_review` → `approved` / `rejected` / `changes_requested`. |
| **Version** | Immutable version number. |

**New image creative form**

| Field | Meaning |
| --- | --- |
| **Image file** | The asset. It is uploaded to storage and gets a public URL; only allowlisted types (PNG/JPEG/GIF/WebP) are accepted. |
| **Destination URL** | Where a click goes. Served only through a single-use, short-lived click token — never an open redirect. |
| **Width / Height** | Render size in pixels. |

> Video and HTML5-bundle creation from the UI are planned; HTML5 bundles are
> malware-scanned (ClamAV) and may only be submitted when the scan is clean.

---

## Review (`/review`)

The **approval queue** — creatives waiting for a decision (`submitted` /
`in_review`). The headline guarantee: *only approved creatives serve.*

**Per-row actions**

| Action | Effect |
| --- | --- |
| **Start review** | Move a submitted creative into `in_review`. |
| **Approve** | Approve it (subject to four-eyes; blocked if you submitted it). |
| **Reject** | Reject it. |
| **Request changes** | Send it back for edits. |

Every decision is audited (who, when, before→after). An invalid transition is
refused (409); a self-approval is refused (403) unless an audited override.

---

## Metrics (`/metrics`)

Aggregate delivery performance for the last 30 days (no visitor identifiers).

**KPI cards** — totals across the window:

| Card | Meaning |
| --- | --- |
| **Impressions** | Counted ad views (idempotent beacons; replays don't inflate). |
| **Clicks** | Counted clicks (via the click-token redirect). |
| **CTR** | Clicks ÷ impressions. |
| **Fill rate** | Fills ÷ serve requests (how often a request returned an ad). |

**Daily table** — date, impressions, clicks, CTR per day. Sensitive
per-visitor-bucket breakdowns require an extra capability and are audited.

---

## Marketplace (`/marketplace`)

Advertisers, pricing rules and campaigns. Money is integer **net minor units
(JPY cents)** — NeNe Serve is tax-neutral and is *not* the books of account; the
money source of truth is NeNe Invoice (ADR 0014).

**Advertisers** — name, status. *New advertiser:* a name.

**Pricing rules** (immutable, versioned):

| Column / field | Meaning |
| --- | --- |
| **Name** | Label. |
| **Model** | `cpm` (per 1,000 impressions), `cpc` (per click), or `flat`. |
| **Rate (cents)** | Net minor units. CPM example: `50000` = ¥500 per 1,000 impressions. |
| **Version** | Bumps on each change (the old version is preserved). |

**Campaigns**:

| Column / field | Meaning |
| --- | --- |
| **Name** | Label. |
| **Advertiser** | Owner (chosen from your advertisers). |
| **Pricing rule** | The rule applied (chosen from your pricing rules). |
| **Budget (cents)** | Net minor-unit cap. Spend is derived and never overspends. |
| **Status / Funding** | Lifecycle and funding state; only an active + funded campaign within budget is billable. |

---

## Users (`/users`)

Operators in your organization (requires user-management permission).

- **List** — email and role.
- **Invite a user** — enter an email and role; the person gets a single-use
  set-password link by email. The banner reports whether the invite email was
  actually sent (which needs SMTP configured in Settings). The raw token is
  never shown in the UI.

---

## Settings (`/settings`)

Outbound email (**SMTP**) used for invitations and test mail.

| Field | Meaning |
| --- | --- |
| **Host / Port** | Your SMTP server (e.g. dev Mailpit `mailpit:1025`). |
| **Encryption** | `none`, `starttls`, or `tls`. |
| **Username** | SMTP user (blank if the server needs no auth). |
| **Password** | SMTP password — **encrypted at rest** and never returned by the API. Leave blank to keep the stored one. |
| **From address / From name** | The sender shown on outgoing mail. |

- **Save** — stores the settings (audited).
- **Send test email** — sends a test to your own address through the saved
  settings, so you can confirm delivery before inviting people. In dev the mail
  appears in Mailpit (`http://localhost:8013`).

---

## Statuses glossary

| Status | Where | Meaning |
| --- | --- | --- |
| `draft` / `submitted` / `in_review` / `approved` / `rejected` / `changes_requested` | Creative | Review lifecycle; only `approved` serves. |
| `active` / `archived` | Placement | Archived is an additive tombstone (never deleted). |
| `pending` / `clean` / `flagged` | HTML5 scan | Only `clean` may proceed; an unreachable scanner stays `pending` (fail-closed). |
| `unfunded` / `funded` | Campaign | Only funded + active + in-budget serves billably. |

---

## Good to know (governance)

- **Nothing governed is hard-deleted.** "Delete" = archive / disable / tombstone;
  the application database role has no DELETE on governed tables. Every governed
  write is audited (who / when / before→after).
- **Privacy by default.** No raw PII in event tables; measurement is consent-gated;
  opt-out is honoured.
- **Fail closed.** Missing capability, bad token, unconfigured scanner, or empty
  serve all fail safe rather than leak or over-serve.

See also: [tutorial](../tutorial/first-campaign.md) ·
[API contracts](../api/) · [terminology](../explanation/terminology.md).
