# Roadmap

## Phase 0: Governance ✅

Scope, measurement, serve.js spec, six-locale ADR, sibling map, creative sandbox ADR.

## Phase 1: Foundation ✅

Org/auth, placement + image creative, serve API, imp/click, daily metrics CSV,
locale CI. **API security baseline** (three surfaces, origin gating, rate limits,
short-lived click tokens, no open redirect) is gated by `docs/review/api-security.md`
(ADR 0018/0019). Image creatives still pass the **approval gate** (only `approved`
serves) — full review queue is Phase 2 (ADR 0020).

## Phase 2: Rich creatives ✅

Video, HTML5 bundle + review queue, frequency cap, charts polish. **Consent UI
(six-locale) + data-subject-request tooling** land here — gated by privacy
compliance (ADR 0016/0017); frequency-cap buckets are consent-gated. **Creative
review workflow + malware scan + sandbox/CSP** (ADR 0020/0021) gate html5/video,
per `docs/review/creative-review.md`.

## Phase 3: Marketplace ✅

Advertiser, budget, Invoice handoff, pause on budget. **Gated by** billing &
accounting compliance (ADR 0014 money SSOT boundary, ADR 0015 billing-relevant
measurement integrity); tax-neutral, money SSOT = Invoice.

## Phase 4: Ecosystem ✅

Deal/Records/Concierge HTTP integrations, MCP write plans with audit.

## Operable v1 (in progress)

`serve.js` embed client, admin SPA (`frontend/`, six-locale, mock-first) with
read screens + create forms, email-based provisioning (SMTP → invite →
set-password), asset upload + ClamAV malware scan. Remaining before production:
billing-period close/handoff + edit forms in the console, video/HTML5 upload UI,
and deploy hardening (migrations on deploy, HTTPS/secrets, shared
token/rate-limit/frequency store for multi-host).

## Not on roadmap

Contact forms, Concierge editor, invoice PDFs, bank tools, external DSP/RTB, tax
computation, qualified-invoice issuance, payment processing (money SSOT = Invoice).

Last updated: 2026-06-06
