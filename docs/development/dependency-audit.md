# Dependency vulnerability gate (frontend)

Every PR runs a dependency audit as a **merge gate**. This document says what the gate is,
how an exception is granted, and what is currently excepted.

- Config: [`frontend/audit-ci.jsonc`](../../frontend/audit-ci.jsonc) (the file itself carries
  the reasoning for each entry — keep the two in sync)
- Command: `npm run audit` (from `frontend/`)
- CI: the `Audit (fail on high/critical)` step of the `frontend` job in `.github/workflows/ci.yml`

## The gate

`audit-ci` fails the build on any **high** or **critical** advisory that is not explicitly
allowlisted. Moderate and below do not fail (they are still reported).

We use `audit-ci` rather than bare `npm audit --audit-level=high` for one reason: **`npm audit`
has no way to record a reasoned exception.** Without one, the only ways past a
not-yet-fixable advisory are to lower the severity threshold or drop the step — both of which
blind the gate to *everything*, not just the advisory in question.

## Rules for an exception

1. **Per advisory id, never per severity.** Allowlist `GHSA-…`; do not raise `--audit-level`
   and do not set `high: false`. A new advisory must still fail the build the day it lands.
2. **The reason must be measured, not assumed.** State why the vulnerable code path does not
   exist *in this codebase*, and how that was checked (a grep, a build artifact, a config).
   "We probably don't use that" is not a reason.
3. **Every entry has an expiry** and a named condition that removes it (an upgrade wave, an
   upstream fix). An expired entry is a task — re-argue it in a PR; do not extend it by reflex.
4. **Prefer the fix.** If a patched version exists in a range we can take, take it. An
   exception is only for "no fix exists that we can adopt".

Rule 4 is not decorative. Adopting this gate (#195) started from **high 5 / moderate 2 / low 1**
and ended at **one** entry, because everything else had an adoptable fix:

| Package | Before | After | How |
| --- | --- | --- | --- |
| `react-router-dom` / `react-router` | 7.16.0 | 7.18.1 | within the existing `^7.9.6` range |
| `postcss` | 8.5.15 | 8.5.24 | transitive, `npm update` |
| `vite` | 8.0.14 | 8.1.5 | within the existing `^8.0.12` range |
| `esbuild` | 0.27.7 | 0.28.1 | `overrides` |
| `js-yaml` | 4.1.1 | 4.3.0 | `overrides` |
| `brace-expansion` (v5 chain) | 5.0.6 | 5.0.8 | **scoped** `overrides` — see below |

### Why the `brace-expansion` override is scoped

`"brace-expansion@5": "^5.0.8"`, not `"brace-expansion": "^5.0.8"`. The blanket form is the
obvious-looking move and it breaks the build:

`eslint-plugin-import` and `eslint-plugin-jsx-a11y` still bundle `minimatch@3.1.5`, which does
`const expand = require('brace-expansion')` and calls it. **brace-expansion@5 exports an object,
not a function**, so under a blanket override any brace pattern throws
`expand is not a function`. Measured here:

```console
$ # blanket override
minimatch('src/a.ts', 'src/*.ts')      → true
minimatch('src/a.ts', 'src/{a,b}.ts')  → THROWS: expand is not a function

$ # scoped override
minimatch('src/a.ts', 'src/{a,b}.ts')  → true
```

serve's lint happened to pass under the blanket form only because no brace pattern reached
minimatch@3 — latent, not safe. It took out `npm run lint` outright on nene-invoice (#732).
Scoping patches the v5 chain and leaves minimatch@3 on its compatible 1.1.16.

## Current exceptions

| Advisory | Package | Why it does not apply here | Expires |
| --- | --- | --- | --- |
| [GHSA-qwww-vcr4-c8h2](https://github.com/advisories/GHSA-qwww-vcr4-c8h2) | `react-router` (7.12.0–8.2.0) | The admin console is a **static SPA built by Vite** with no server runtime of its own — the PHP kernel serves the API and the bundle talks to it over `fetch`. `src/app/router.tsx` uses `createBrowserRouter` with **element-only routes**. The advisory's attack path (a server executing a route action before returning 400) has no counterpart in a client-only bundle. Measured 2026-07-29: no route `action:` / `loader:` keys in the router config, and no `@react-router/dev`, RSC entry, `createStaticHandler`, or `StaticRouterProvider` import anywhere in `src/`. | **2026-08-31** |
| [GHSA-mh99-v99m-4gvg](https://github.com/advisories/GHSA-mh99-v99m-4gvg) | `brace-expansion` (≤ 5.0.7) | **Dev-only** — `npm ls brace-expansion --omit=dev --all` returns `(empty)` (measured 2026-07-29). It reaches the tree solely via eslint plugins and the OpenAPI codegen toolchain; none of it is in the shipped bundle, so an attack means feeding an adversarial brace pattern to a local lint or codegen run. The fix exists **only in 5.0.8** — there is no patched 1.x/2.x (`npm view brace-expansion versions` ends at 1.1.16 / 2.1.3, both inside the range) — and forcing 5.0.8 into the minimatch@3 chain breaks lint, see above. npm's suggested fix is a semver-major *downgrade* of `eslint-plugin-jsx-a11y` to 6.4.1, which is not adoptable. | **2026-08-31** |

For `GHSA-qwww-vcr4-c8h2` there is **no fix available in the 7.x line**: `react-router-dom` ends
at 7.18.1 (measured `npm view react-router-dom version`), and the fix lands in `react-router`
≥ 8.2.1 — a different package and a breaking upgrade. The exception is removed by the
**react-router v8 migration wave** (bundled with the NENE2 RR8 re-evaluation).

`GHSA-mh99-v99m-4gvg` is removed when `eslint-plugin-import` / `eslint-plugin-jsx-a11y` stop
bundling `minimatch@3` (the fleet **eslint 10 wave**) — at that point the blanket override
becomes safe and the entry goes away with it.

## Verifying the gate is sharp

An allowlist that swallows everything is worse than no gate. After changing this config,
prove it still fails — remove the entry and re-run:

```console
$ npm run audit          # as shipped
Found vulnerable allowlisted advisories: GHSA-mh99-v99m-4gvg, GHSA-qwww-vcr4-c8h2.
Passed npm security audit.                            # exit 0
$ npm run audit          # GHSA-mh99-v99m-4gvg removed
Failed security audit due to high vulnerabilities.    # exit 1
$ npm run audit          # GHSA-qwww-vcr4-c8h2 removed
Failed security audit due to high vulnerabilities.    # exit 1
```

Measured 2026-07-29 on #195 — each entry dropped on its own, so both are load-bearing and
neither is masking the other.

## Fleet note

This setup follows the fleet reference implementation (nene-contact #525, 施主 GO 2026-07-29).
The RSC-unused claim above was **re-measured in this tree**, not copied — copying an exception
without re-measuring is exactly the failure mode the rules above exist to prevent.

## Related

- [`coding-standards.md`](./coding-standards.md) — the wider merge-gate set
- Pinning a version to dodge an advisory is a **time-limited** measure, not a fix: the pinned
  version can itself fall inside a later advisory. Prefer ranges, and revisit `overrides`.
