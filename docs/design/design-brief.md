# NeNe Serve — design brief (for Claude Design)

A product brief to orient design work: what NeNe Serve is, who it's for, what it
**will** do, what it **won't** do, the surfaces to design, and the constraints
that shape the UX. This is a design-facing summary; the binding source of truth is
[`scope-contract.md`](../explanation/scope-contract.md) and the linked specs/ADRs.

---

## In one sentence

**NeNe Serve is self-hosted ad serving + analytics**: an operator registers
creatives, binds them to placements, a publisher drops one `serve.js` line on
their own sites, visitors see ads under weight/cap/fallback rules, and Serve
tracks impressions and clicks and reports them — **without becoming a CRM, a
contact inbox, a billing system, or a global ad exchange.**

## What it is (and isn't), at a glance

- It is a **publisher-side** tool: you run ads on **sites you control**, not a
  marketplace that brokers third-party demand.
- It is **governed and auditable** by design — built to be defensible to
  accountants, privacy regulators, and security review. That posture is a
  **feature to express in the UI**, not friction to hide.
- It is **one of several separate products** (Contact, Concierge, Invoice, …)
  that share nothing but HTTP contracts. Serve stays in its lane.

---

## Who it's for (audiences)

| Audience | Role | What they need from the UI |
| --- | --- | --- |
| **Ad-ops lead / tenant admin** (`org_admin`) | Sets up everything, invites teammates, configures SMTP/marketplace | Confidence, speed, a clear "what's live / what's pending" picture |
| **Builder** (`editor`) | Creates placements & creatives, submits for review | Focused creation flows; clear validation; status of their submissions |
| **Reviewer** (`review_creatives`) | Approves/rejects creatives (four-eyes) | A trustworthy review queue; clear "why" and consequences of a decision |
| **Analyst** (`analyst`) | Reads metrics only | Legible, honest charts; no editing affordances |
| **Publisher / site owner** | Pastes `serve.js` on their site | A dead-simple embed snippet; the rendered ad must never break their page |
| **Site visitor** | Sees/clicks the ad | A fast, unobtrusive, privacy-respecting ad; consent honored |
| **AI / automation (MCP)** | Proposes delivery-plan changes | Read-first; writes are explicit, confirmable, audited (not a surprise) |

Personas to design against live in the
[tutorial](../tutorial/first-campaign.md) (Mei the ad-ops lead, Ken the reviewer,
a publisher site).

---

## What we want to do (goals / DO)

The product owns these — design should make them feel first-class:

- **Creatives**: image, hosted video, and **sandboxed HTML5 bundles** (no raw
  third-party tags). A creative is content that must be **reviewed and approved**
  before it can serve.
- **Placements**: named ad slots with a public key for the embed and an allowed-
  origins list.
- **Delivery plan**: weighted rotation, per-creative/placement **caps**, schedule
  windows, and a **default/fallback** creative.
- **Measurement**: trustworthy **impression** and **click** tracking; time-series
  **reporting** with CSV export and placement/creative breakdowns.
- **Review workflow**: a state machine (draft → submitted → in_review → approved /
  rejected / changes_requested) with **four-eyes** approval.
- **Multi-tenant** operation with role-based access; **six-locale** UI and embed
  chrome.
- **Onboarding**: invite teammates by email (single-use set-password link);
  manage outbound SMTP.
- **Marketplace (optional)**: advertisers, versioned pricing rules, campaigns with
  budgets; spend is **derived and capped**, handed to NeNe Invoice for payment.
- **Automation surface**: OpenAPI for admin/public/service; MCP tools that are
  read-first with audited, confirm-then-apply writes.

## What we will NOT do (non-goals / DON'T)

Design should **not** invent UI for any of these — they belong to other products
or are deliberately out of scope:

- ❌ **Contact forms / inquiry inbox** → that's NeNe Contact.
- ❌ **Chat / conversational scenarios** → that's NeNe Concierge.
- ❌ **Quotes, invoices, PDFs, recording payments, tax** → that's NeNe Invoice.
  Serve shows **net counters in cents**, never money documents or tax.
- ❌ **Bank reconciliation, dunning/collection reminders, bank-CSV import.**
- ❌ **A global RTB/DSP / third-party ad exchange or bidding.**
- ❌ **Executing unreviewed third-party ad tags** (raw `<script src=…>`).
- ❌ **Merging form submissions into ad events**, or sharing a database with
  siblings.

If a design idea drifts toward "let's also let them message customers / send an
invoice / pull in bank data," it's out of scope — stop and flag it.

---

## Surfaces to design

1. **Admin console** (`/admin/*`, React SPA) — the main canvas. Screens today:
   - **Login** (org + email + password) and **Set password** (invite landing).
   - **Placements** — list + create.
   - **Creatives** — list + create (image upload today; video/HTML5 planned).
   - **Review** — approval queue with per-row decisions (four-eyes).
   - **Metrics** — KPI cards (impressions, clicks, CTR, fill rate) + daily table.
   - **Marketplace** — advertisers, pricing rules, campaigns (read + create).
   - **Users** — list + invite.
   - **Settings** — SMTP config + send-test.
   - Standalone visual snapshots exist in `static-preview/` (open `index.html`).
2. **The served ad** (public) — what a visitor actually sees: an image/video/
   sandboxed iframe rendered by `serve.js`, plus the (rare) empty/fallback state.
   It must be **unobtrusive and never break the host page**.
3. **Embed ergonomics** — the one-line snippet and any embed "chrome" (e.g. a
   consent affordance) the publisher might show; localized strings.
4. **Transactional email** — the invitation / set-password and SMTP-test emails
   (plain, trustworthy, link-forward).

## Constraints that shape the UX (please honor)

- **Approval gate is the spine.** "Only approved creatives serve" — make review
  status, four-eyes, and "draft vs live" unmistakable everywhere.
- **Nothing governed is deleted.** "Delete" means archive/disable/tombstone.
  Design **archive**, not destruction; never imply permanent removal.
- **Everything is audited.** Surfacing "who changed what, when" is on-brand;
  destructive-looking actions should feel deliberate, not casual.
- **Privacy by default.** No raw PII; measurement is consent-gated and opt-out
  aware; metrics are **aggregate only**. Don't design visitor-level surveillance.
- **Security is visible trust.** Fail-closed states (insufficient permission,
  unconfigured SMTP, scanner pending, empty serve) need calm, clear messaging —
  not dead ends or scary errors. No open redirects; HTML5 ads are sandboxed.
- **Money is tax-neutral counters.** Show budgets/rates as net JPY (minor units);
  never tax, never an invoice. Defer to "settled in NeNe Invoice."
- **Six locales incl. CJK** (en, ja, zh-Hans, ko, de, es): layouts must tolerate
  longer strings and CJK; a language switch is always reachable.
- **Accessibility**: keyboard-navigable, labelled fields, sufficient contrast in
  both light and dark themes; the served ad must not trap focus.

---

## Tone & visual direction

- **Calm, operator-grade, trustworthy.** This is a back-office tool people rely
  on to run live ads correctly and defensibly. Favor clarity and legibility over
  flash. Data-dense but not noisy.
- Today the console inherits a shared "Calm" design system (light default + dark,
  theme-token driven) from the sibling products. **A NeNe Serve-specific identity
  is welcome** — but keep the token-based theming so a single theme file can
  restyle the whole app.
- Status is a primary visual language: draft/submitted/approved, active/archived,
  clean/flagged, funded/unfunded — design a consistent, legible status system.
- The **served ad** is the opposite of the console: invisible infrastructure.
  Its only "design" is to render the creative faithfully and get out of the way.

## What "good" looks like

- An operator can go **login → configure → invite → build → approve → embed →
  see it live → read results** without confusion (the tutorial loop).
- A reviewer trusts the queue enough to approve confidently.
- A publisher copies one line and the ad appears — and if there's no ad, their
  page is unaffected.
- The governance posture (approved-only, audited, privacy-safe) is **felt as
  trust**, not encountered as friction.

## Open questions for design

- A Serve-specific brand/identity vs. continuing the shared Calm system.
- Status taxonomy & color semantics across creatives/placements/campaigns/scan.
- Delivery-plan editing UX (weights/caps/schedule) — not yet built; high-value.
- Empty/error/permission states as a coherent set (fail-closed, reassuring).
- Consent affordance for the embed chrome (six-locale).

---

## References

- Binding scope: [`scope-contract.md`](../explanation/scope-contract.md)
- Console help: [`reference/admin-console.md`](../reference/admin-console.md)
- End-to-end tutorial: [`tutorial/first-campaign.md`](../tutorial/first-campaign.md)
- Embed contract: [`serve-embed-spec.md`](../explanation/serve-embed-spec.md)
- Measurement / privacy / security / billing / creative-review specs in
  [`docs/explanation/`](../explanation/); decisions in [`docs/adr/`](../adr/)
- Static screen snapshots: `static-preview/index.html`
