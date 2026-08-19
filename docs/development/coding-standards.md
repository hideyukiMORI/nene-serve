# Coding Standards (index)

- NENE2 upstream: https://github.com/hideyukiMORI/NENE2/blob/main/docs/development/coding-standards.md
- Local: [`nene2-compliance.md`](./nene2-compliance.md), [`backend-standards.md`](./backend-standards.md), [`i18n.md`](./i18n.md)
- Terminology: [`../explanation/terminology.md`](../explanation/terminology.md)

Namespace `NeneServe\`. JSON snake_case. Money in cents.

> `cents` = the currency's **minor unit**, not 1/100 of the display amount.
> **JPY has zero decimal places (ISO 4217), so `*_cents` stores whole yen — never multiply by 100.**
> Example: ¥1,500 is stored as `1500`. A value like `116480` means ¥116,480, not ¥1,164.80.
