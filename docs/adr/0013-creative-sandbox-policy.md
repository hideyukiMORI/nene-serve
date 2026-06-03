# ADR 0013: Creative Sandbox Policy

## Status

accepted

## Context

Operators want HTML5 mini-games and animated units. Arbitrary third-party ad tags introduce XSS and malvertising risk.

## Decision

| creative_type | MVP | Policy |
| --- | --- | --- |
| `image` | yes | Hosted or HTTPS URL allowlist |
| `video` | Phase 2 | MP4/WebM hosted; no autoplay with sound default |
| `html5_bundle` | Phase 2 | Zip uploaded by admin; virus scan; served in **sandboxed iframe** with strict CSP |
| `third_party_tag` | **no** (MVP) | Requires security review ADR to enable |

Games and "JS movies" ship as **reviewed html5_bundle**, not raw `<script>` injection from the admin textarea.

## Consequences

- Operators cannot paste AdSense tags in MVP.
- Creative review workflow (approve/reject) required before `active` status.

## Related

- [`../explanation/scope-contract.md`](../explanation/scope-contract.md) X7
