# ADR 0012: Impression and Click Definitions

## Status

accepted

## Decision

MVP definitions in [`../explanation/measurement-spec.md`](../explanation/measurement-spec.md) are binding:

- Impression = successful serve response + client beacon with token
- Click = redirect endpoint hit before outbound URL

Future viewability (50% in view 1s) requires new ADR and metric series — do not retroactively change MVP series.

## Related

- ADR 0010
