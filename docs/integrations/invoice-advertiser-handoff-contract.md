# Invoice Advertiser Handoff Contract (draft)

**Status: draft** — activates when Invoice exposes advertiser charge endpoints.

## Purpose

When Serve runs **marketplace mode**, advertiser spend is tracked in Serve (`budget_cents`, impressions delivered) but **money SSOT is NeNe Invoice**.

## Serve stores

- `advertiser_id`, `budget_cents`, `spent_cents` (derived from delivery)
- `invoice_client_id`, `invoice_payment_id` after handoff

## Serve calls Invoice

- Create or sync advertiser as Invoice **client** (draft)
- Post charge or invoice line items per billing period (TBD with Invoice ADR)
- Poll payment status to pause campaigns when budget exhausted

## Serve must not

- Compute consumption tax
- Issue qualified invoices
- Store card numbers

## Related

- ADR 0002

Last updated: 2026-06-03
