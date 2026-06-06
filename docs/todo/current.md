# Current TODO

**Phase 0 — Governance** ✅ complete on `main` (2026-06-03)

## Phase 0 checklist

- [x] Public repo scaffold + six locale placeholders
- [x] Scope contract, measurement spec, serve.js spec, privacy/ad compliance
- [x] ADRs 0001–0013 (incl. six-locale, Contact/Concierge split, creative sandbox)
- [x] Sibling map (Invoice handoff draft; no Clear/Profile paths)
- [x] GitHub Issue #1 closed

## Next (Phase 1 — milestone "Phase 1: Foundation")

- [x] #10 (A) Runtime scaffold — NENE2 layout, `GET /health`, `composer locales:check` stub, Docker stack (8910/8911/3392)
- [x] #11 (B) Multi-tenant org/auth model — roles/capabilities, JWT bearer auth, tenant isolation (ADR 0006/0018)
- [x] #12 (C) Three API surfaces + serve API baseline — origin gating, rate limits, short-lived single-use click tokens, no open redirect, idempotent impression, scoped service tokens (ADR 0018/0019)
- [x] #13 (D) Placement + image creative behind approval gate — review state machine, four-eyes, immutable versions, image acceptance, audit (ADR 0020/0021)
- [x] #14 (E) Impression/click measurement + daily metrics CSV — append-only events, consent-gated visitor bucket, opt-out, no raw PII, CSV export (ADR 0012/0015/0017)
- [x] #15 (F) Locale CI — six-locale catalog check: nested-key parity + pluralization, GitHub Actions (ADR 0011)

**Phase 1 — Foundation ✅ complete (2026-06-04): #10–#15 all merged.**

## Next (Phase 2 — milestone "Phase 2: Rich creatives")

- [x] #24 (A) Video creative acceptance — MP4/WebM, poster, max duration, no autoplay-with-sound (ADR 0021)
- [x] #25 (B) HTML5 bundle + malware scan + sandbox/CSP + review queue — content policy, scan-clean submit gate, opaque frame token, strict-CSP sandbox (ADR 0020/0021)
- [x] #26 (C) Frequency cap (consent-gated visitor buckets) — per-bucket daily cap, fail-open without consent (ADR 0017)
- [x] #27 (D) Consent UI (six-locale) + DSR tooling — consent strings, consent_state recorded, export/erasure (additive tombstone) (ADR 0016/0017)
- [x] #28 (E) Reporting polish — JSON time-series metrics (CTR + fill rate), admin + service (measurement-spec)

**Phase 2 — Rich creatives ✅ complete (2026-06-04): #24–#28 all merged.**

## Next (Phase 3 — milestone "Phase 3: Marketplace")

- [x] #47 (A) Money primitives + Advertiser + PricingRule (versioned, JPY net, no tax/float) (ADR 0014/0015)
- [x] #48 (B) Campaign + budget + funding + billable spend accrual — derived reproducible spend, cap/pause_on_budget_exhausted (no overspend), only active+funded serves billable
- [x] #49 (C) BillingPeriod + tamper-evident SpendSnapshot + audited close — versioned/reproducible snapshot, immutable on close (re-close 409)
- [x] #50 (D) Invoice handoff (idempotent on external_reference) + reconciliation + HTTP client (net-only, failure-isolated)
- [x] #51 (E) Billing-relevant statutory retention + legal hold — two regimes, legal-hold gate, privileged audited purge (closes Integrity #41)

**Phase 3 — Marketplace ✅ complete (2026-06-04): #47–#51 all merged.**
**Integrity & audit hardening ✅ complete: #36–#41 (#41 closed by #51).**

## Next (Phase 4 — milestone "Phase 4: Ecosystem")

- [x] #57 (A) Upstream HTTP client framework + Records read client (asset metadata) (ADR 0002)
- [x] #58 (B) Deal opportunity handoff — net-only, idempotent on external_reference, audited, failure-isolated
- [x] #59 (C) Concierge conversion beacon — append-only conversion, no Contact submission, opt-out aware, in reporting (ADR 0009)
- [x] #60 (D) MCP write-plan mechanism — propose → confirm token → apply, read-first, audited, write:delivery_plan scope

**Phase 4 — Ecosystem ✅ complete (2026-06-04): #57–#60 all merged.**

## Road to v1 (operable product)

- [x] #79 **serve.js embed client** (#1 critical path) — `public_html/serve.js`, vanilla/CSP-friendly, image/video/sandboxed-html5 render + viewable impression beacon + click-token wrap; served statically; jsdom test on the real artifact. PR #80.
- [~] #2 Admin create forms — marketplace (advertiser/pricing/campaign, PR #82) + serving (placement/image-creative, PR #84) done; the operate loop is now drivable in the console. Remaining: billing-period close/handoff actions, edit forms, video/html5 creative (needs #4).
- [x] #3 Provisioning (invite-link via email) — mail infra (#86), admin-managed SMTP encrypted at rest + test (#87), invite backend (#88), **provisioning UI (#90: Users+invite, Settings/SMTP, set-password)**. Operator console onboards teammates end-to-end (configure SMTP → invite → set password → sign in). Remaining sub-items: service-token issuance UI, first-org bootstrap CLI.
- [x] Prod bug fixed (#92): all 9 `REPLACE INTO` → `INSERT .. AS new ON DUPLICATE KEY UPDATE` (no DELETE needed); verified create+update under the locked `nene` role on MySQL. Create/edit flows now work in production.
- [x] #4 Asset upload + storage + real malware scanner — local `Storage` + image/video upload + public serving (#4a, PR #93); **ClamAV** clamd scanner, fail-closed (#4b, PR #95, verified clean/EICAR live); image upload UI in the creative form (#4c, PR #97). Follow-ups: video/HTML5-bundle upload UI + multipart for large video.
- [ ] Production deploy hardening (migrations on deploy, HTTPS/secrets, shared token/rate-limit/frequency store for multi-host).
- [ ] Real sibling integrations (Invoice/Deal/Records) when those services exist.

## Post-roadmap hardening

- [x] #65 OpenAPI 3.1 contracts for the three surfaces (`docs/api/` public · admin · service) — RFC 9457 Problem Details, registry-aligned operationIds, MCP→service-doc-only; `tests/Api/OpenApiContractTest.php` asserts documented paths == Kernel routes (bidirectional, no drift). PR #66.
- [x] #67 Wire PDO repositories into production kernel boot — `Support\KernelFactory` (DB_HOST → database mode, else file/dev); migration 0029 `service_tokens` + `PdoServiceTokenRepository` + grants (append-only, no DELETE) closes the service-token gap; verified end-to-end on docker MySQL. PR #68.
- [x] #69 Admin SPA scaffold (`frontend/`) following sibling NeNe convention — React+Vite+TS, FSD, Tailwind v4, TanStack Query, MSW mock-first, Storybook, six-locale i18n, openapi-typescript codegen; login + placements vertical slice; CI `frontend` job. PR #70.
- [x] #71 Admin read/list endpoints (step ①) — `GET /admin/placements`(+`/{id}`), `GET /admin/creatives/{id}`, `GET /admin/campaigns`, `GET /admin/pricing-rules`; `Placement::toAdminArray()`; spec+terminology updated; verified end-to-end on MySQL. PR #72.
- Step ② screens (FE), wired to the real admin endpoints:
  - [x] Creatives & review (list + review-queue with actions; nav Placements·Creatives·Review). PR #74.
  - [x] Metrics dashboard (`GET /admin/metrics`; KPI cards + daily CTR/fill table). PR #76.
  - [x] Marketplace (advertisers, pricing rules, campaigns — read; money via formatMoneyJpy). PR #78.
  - [ ] Billing-period actions (close/handoff), create forms, detail views (placement/creative); CJK/Hangul webfonts; serve re-theme.

## Integrity & audit hardening (ADR 0022) — before Phase 3

- [x] Binding doc + ADR 0022 + self-review (`docs/explanation/audit-and-data-integrity-compliance.md`)
- [x] FK `ON DELETE CASCADE` → `RESTRICT` on governed tables (users/placements/creatives/audit_events) — #36; org delete now refused (error 1451), audit trail survives
- [x] Hash-chained tamper-evident `audit_events` — #37; per-tenant SHA-256 chain + verifier (edit/gap/reorder detectable)
- [x] Audited-write coverage + structured before→after; mutation+audit atomic — #38; TransactionManager (Null+Pdo), all mutating use cases wrapped, archive now audited
- [x] DB grants: app role without `DELETE`/`TRUNCATE` on governed tables; `archived_at`/`disabled_at` tombstones — #39; `database/grants.sql`, Placement.archive(), presentation table `user_preferences`
- [x] Sensitive-read audit (`include_sensitive`, DSR export, PII-link reads) — #40; `view_sensitive_metrics` gate + `metrics.read_sensitive` audit; aggregate reads unaudited
- [x] Retention + legal-hold purge process (privileged role, audited) — #51

## Governance hardening (2026-06-04)

- [x] Billing & accounting compliance (binding): `docs/explanation/billing-and-accounting-compliance.md`
- [x] ADR 0014 (Serve is not the books of account; money SSOT = Invoice; tax-neutral)
- [x] ADR 0015 (billing-relevant measurement integrity)
- [x] Self-review checklist: `docs/review/billing-compliance.md`
- [x] Hardened Invoice handoff contract; registered marketplace billing terminology
- [x] Privacy/data-protection (binding): `docs/explanation/privacy-and-ad-compliance.md`
- [x] ADR 0016 (self-hosted data-controller model), ADR 0017 (consent & lawful basis)
- [x] Self-review checklist: `docs/review/privacy-compliance.md`; registered privacy/consent terminology
- [x] API security (binding): `docs/explanation/api-security-spec.md`
- [x] ADR 0018 (API surface & auth model), ADR 0019 (token & redirect safety)
- [x] Self-review checklist: `docs/review/api-security.md`; registered Problem Details slugs + operationId stems
- [x] Creative review & safety (binding): `docs/explanation/creative-review-and-safety.md`
- [x] ADR 0020 (review workflow & approval gating), ADR 0021 (acceptance & sandbox safety)
- [x] Self-review checklist: `docs/review/creative-review.md`; registered review states / scan / creative Problem Details slugs

## Test & quality hardening (2026-06-05 → 06)

- [x] Pure-logic + domain-entity unit coverage; fixed `DestinationUrl` IPv6 loopback
      bug (PRs #107–#108)
- [x] SQLite in-memory test harness + PDO repository read coverage; injected repos
      into `ServeCreativeUseCase` and deduped `Record*` use-cases to test the live
      path (PR #109)
- Backend suite now **470 tests** (PHPStan level 8 + PSR-12 green); frontend **33
  tests** (type-check + lint + knip + Storybook green)

## Tenant resolution modes (2026-06-06)

- [x] Pluggable org resolution for the admin surface (NeNe Records parity, ADR 0006):
      `Tenant\Resolution\` — `OrgResolutionMode` (login·single·subdomain·path·custom_domain)
      + Env/Subdomain/PathPrefix/CustomDomain strategies + `OrgResolverMiddleware`
      (fails closed; path-prefix strip before routing). Default `login` keeps the
      JWT-only pipeline unchanged.
- [x] `AdminAuthMiddleware` reconciles the JWT against the URL-resolved tenant
      (cross-tenant token → 403; superadmin may act within the resolved org)
- [x] `organizations.custom_domain` (migration 0033) + `findByCustomDomain()`
- [x] Wired via `TENANT_RESOLUTION` / `TENANT_ORG_SLUG` / `TENANT_BASE_DOMAIN`
      (env); verified end-to-end on docker MySQL (subdomain mode: resolved→401,
      unknown→404, bare→404). +22 unit tests (492 total)
- Fixed a pre-existing `docker-compose.yml` DB-env bug: NENE2 reads `DB_NAME` /
  `DB_USER`, not the Laravel-style `DB_DATABASE` / `DB_USERNAME`

### Login adapts to the mode (#112, 2026-06-06)

- [x] `GET /admin/tenant-context` (open) → `{ mode, organization }`; the admin SPA
      reads it before sign-in. `OrgResolverMiddleware` now resolves the open admin
      routes (login / invitations / tenant-context) best-effort — attaches the
      tenant when available, never fails closed there
- [x] `LoginHandler`: a URL-resolved tenant is authoritative — the body
      `organization` is ignored, so a tenant URL only signs into its own org
- [x] Frontend `LoginView` hides the org field and shows the resolved org name in
      URL modes (six-locale `login.signingInTo`); `useTenantContext` query + MSW
      mock. Verified end-to-end on docker MySQL
- Follow-up: DB-backed mode + admin UI to switch modes (Records #211–213)

## Notes

- Engineering docs: **English**. UI: **en, ja, zh-Hans, ko, de, es**.
- Ports (80xx lane): API **8010** / phpMyAdmin **8011** / Mailpit **8013** / MySQL **3380** / ClamAV **3308** / frontend **5180** / Storybook **6107**.
- **Serve is tax-neutral and not the books of account** (ADR 0014/0015); Phase 3 marketplace is gated by `docs/review/billing-compliance.md`.
- **Operator is the data controller; privacy by default** (ADR 0016/0017); measurement changes are gated by `docs/review/privacy-compliance.md`.
- **Three separated API surfaces; fail closed** (ADR 0018/0019); endpoint changes are gated by `docs/review/api-security.md`.
- **Only approved creatives serve** (ADR 0020/0021); creative changes are gated by `docs/review/creative-review.md`.

Last updated: 2026-06-06 (test harness + coverage to 470; audit-round refactors; admin SPA read screens + create forms, provisioning, asset upload + ClamAV all landed)
