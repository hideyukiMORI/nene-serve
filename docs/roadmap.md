# Roadmap

## Phase 0: Governance ✅

Scope, measurement, serve.js spec, six-locale ADR, sibling map, creative sandbox ADR.

## Phase 1: Foundation

Org/auth, placement + image creative, serve API, imp/click, daily metrics CSV,
locale CI. **API security baseline** (three surfaces, origin gating, rate limits,
short-lived click tokens, no open redirect) is gated by `docs/review/api-security.md`
(ADR 0018/0019).

## Phase 2: Rich creatives

Video, HTML5 bundle + review queue, frequency cap, charts polish. **Consent UI
(six-locale) + data-subject-request tooling** land here — gated by privacy
compliance (ADR 0016/0017); frequency-cap buckets are consent-gated.

## Phase 3: Marketplace

Advertiser, budget, Invoice handoff, pause on budget. **Gated by** billing &
accounting compliance (ADR 0014 money SSOT boundary, ADR 0015 billing-relevant
measurement integrity); tax-neutral, money SSOT = Invoice.

## Phase 4: Ecosystem

Deal/Records/Concierge HTTP integrations, MCP write plans with audit.

## Not on roadmap

Contact forms, Concierge editor, invoice PDFs, bank tools, external DSP/RTB, tax
computation, qualified-invoice issuance, payment processing (money SSOT = Invoice).

Last updated: 2026-06-04
