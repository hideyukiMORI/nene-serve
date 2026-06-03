# ADR 0008: English-Only Repository Documentation

## Status

accepted

## Context

The **application** targets six locales (ADR 0011). Engineering contracts (OpenAPI, ADRs, scope, measurement) must stay unambiguous for global contributors and AI agents.

## Decision

| Surface | Language |
| --- | --- |
| `docs/`, `README.md`, `AGENTS.md`, `.cursor/rules/` | **English only** |
| `locales/*.json` | **Six application locales** |
| GitHub Issues / PRs (recommended) | English |
| Conventional Commits | English |

Marketing copy for Japan or other markets lives outside this repo (e.g. publication-strategy) unless later ADR changes policy.

## Consequences

- UI translators work in `locales/`; engineers do not fork ADRs per language.
- CI validates locale key parity, not translated ADRs.

## Related

- ADR 0011
