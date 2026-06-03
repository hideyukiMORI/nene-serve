# Contributing

## Required reading

- [`docs/explanation/scope-contract.md`](explanation/scope-contract.md)
- [`docs/explanation/terminology.md`](explanation/terminology.md)
- [`docs/development/i18n.md`](development/i18n.md)
- [`docs/workflow.md`](workflow.md)

## Policy

Issue-driven workflow, English commits (ADR 0008), six locale files for any UI string change.

Do not introduce Clear, Contact, or Concierge domain code into this repository.

## Secrets

JWT secrets, Invoice service tokens, Slack webhooks — `.env` only.
