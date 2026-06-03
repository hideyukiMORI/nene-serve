# ADR 0014: NeNe Serve Is Not the Books of Account (Money SSOT Boundary)

## Status

accepted

## Context

From Phase 3, NeNe Serve runs an optional **marketplace** where advertisers fund
campaigns. This introduces money. A finance/tax professional (会計士 / 税理士)
must be able to review the system and find a clean, defensible separation between
**measurement/delivery** and **accounting/tax**.

Two failure modes must be designed out:

1. Serve drifting into being a second, unauthoritative ledger that disagrees with
   the real books.
2. Serve making tax determinations (consumption tax, qualified invoice) that are
   not its responsibility and that no professional has signed off.

The sibling **NeNe Invoice** already owns Japanese consumption tax, the qualified
invoice system (インボイス制度), and electronic record retention — see its
`accounting-compliance.md`. Serve must not duplicate or pre-empt that.

## Decision

1. **Serve is not the books of account.** Serve holds measurement events and
   operational money **counters** (`budget_cents`, `spent_cents`), not journal
   entries, ledgers, accounts receivable, or financial statements.
2. **NeNe Invoice is the money SSOT** for advertiser money. Payments, qualified
   invoices, and tax live there.
3. **Serve is tax-neutral.** It makes **no** tax determination — never computes,
   rounds, classifies, or displays consumption tax or any tax; never issues a
   qualified invoice; never records a payment as authoritative.
4. **Serve hands off a net, tax-free taxable base** plus advertiser identity and
   period to Invoice via HTTP (ADR 0002). The applicable jurisdiction's tax law
   is applied by Invoice, not Serve.
5. **Money representation:** integer minimum currency units (`*_cents`); no
   float/DECIMAL; JPY only in Phase 3; net amounts only (no tax component).
6. **Deviation gate:** any departure requires an ADR with **tax/accounting
   professional sign-off**, per
   [`../explanation/billing-and-accounting-compliance.md`](../explanation/billing-and-accounting-compliance.md) §0.

## Consequences

**Benefits**

- One authoritative source for money and tax; no reconciliation war between two
  ledgers.
- Serve stays jurisdiction-neutral and globally deployable (six locales) without
  encoding any country's tax code.
- Clear audit story: a reviewer follows money to Invoice, substantiation to
  Serve.

**Costs**

- Serve cannot answer "how much tax / what's the invoice" on its own — by design.
- Requires a disciplined handoff + reconciliation contract (ADR 0015).

## Related

- [`../explanation/billing-and-accounting-compliance.md`](../explanation/billing-and-accounting-compliance.md) (binding)
- [ADR 0015](0015-billing-relevant-measurement-integrity.md)
- ADR 0002 (siblings via HTTP only)
- [`../integrations/invoice-advertiser-handoff-contract.md`](../integrations/invoice-advertiser-handoff-contract.md)
