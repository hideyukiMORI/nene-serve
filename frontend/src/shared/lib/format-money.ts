/**
 * FLEET CANON: `*_cents` is the currency's minor unit, not 1/100 of the display
 * amount. JPY has zero decimal places (ISO 4217), so `*_cents` stores whole yen
 * — never multiply by 100. ¥1,500 is stored as `1500`.
 *
 * THIS FILE DOES NOT FOLLOW THAT CANON TODAY. Serve stores x100 values, and the
 * helper below divides by 100 to compensate. That is a known deviation, not the
 * standard. Correction is tracked separately as the fleet money-unit
 * remediation (order 2 of: 0. define -> 1. deal -> 2. serve -> 3. clear);
 * background in `_work/reports/2026-08-20-money-unit-archaeology.md`.
 *
 * DO NOT COPY THIS FILE AS A TEMPLATE. This file's first six lines are
 * identical to `nene-deal`'s copy — the deviation propagated by copying code,
 * not by any decision made here.
 */

/**
 * Format an integer JPY minor-unit (cents) amount as a localized currency
 * string. Amounts are stored as cents (1/100 yen) across the API; display
 * rounds to whole yen.
 *
 * @param amountCents integer minor units (JPY cents)
 * @param locale BCP 47 locale tag (e.g. `ja`, `en`)
 */
export function formatMoneyJpy(amountCents: number, locale: string): string {
  const yen = Math.round(amountCents / 100)
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency: 'JPY',
    maximumFractionDigits: 0,
  }).format(yen)
}
