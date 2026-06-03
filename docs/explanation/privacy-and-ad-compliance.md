# Privacy and Ad Compliance (binding)

Engineering rules for lawful, transparent ad measurement. **Not legal advice.**

---

## DO

| # | Rule |
| --- | --- |
| P1 | Document **measurement cookies/storage** in operator-facing privacy template (six locales) |
| P2 | Support **opt-out** flag per placement (`measurement_enabled=false` serves creative without tracking beacons — ADR-gated) |
| P3 | **Retention** limits on impression/click rows (configurable per org) |
| P4 | **Hash or bucket** visitor identifiers; no raw email in event tables |
| P5 | **Allowed origins** on every placement |
| P6 | **Advertiser billing** only via Invoice handoff — Serve stores budget cents + external refs |
| P7 | **Audit** changes to weights, caps, and active creatives |
| P8 | Export and delete metrics subject to operator request (GDPR-style tooling Phase 2+) |

---

## DON'T

| # | Rule |
| --- | --- |
| N1 | Log full page URLs with query strings containing tokens |
| N2 | Sell visitor profiles to third parties |
| N3 | Fingerprint across unrelated publishers without disclosure |
| N4 | Host malvertising — unapproved script tags (ADR 0013) |

---

## Publisher responsibility

Operators must publish their own privacy policy linking measurement practices. Serve provides configurable disclosure snippets in all six locales.

---

## Related

- [`measurement-spec.md`](./measurement-spec.md)
- [`../review/compliance.md`](../review/compliance.md)

Last updated: 2026-06-03
