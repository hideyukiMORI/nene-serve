# Creative Review & Sandbox Safety — Binding Rules

**Status: binding (non-negotiable).** Source of truth for how creatives are
reviewed, accepted, and safely rendered in NeNe Serve. A security reviewer must
be able to find **zero deviations** from the rules below. Deepens
[ADR 0013](../adr/0013-creative-sandbox-policy.md).

These are **MUST** requirements. Where a rule here conflicts with UX,
performance, or operator convenience, **safety wins**.

Read first: [ADR 0013](../adr/0013-creative-sandbox-policy.md),
[ADR 0020](../adr/0020-creative-review-workflow.md),
[ADR 0021](../adr/0021-creative-acceptance-and-sandbox-safety.md),
[`api-security-spec.md`](./api-security-spec.md),
[`scope-contract.md`](./scope-contract.md),
self-review [`../review/creative-review.md`](../review/creative-review.md).

---

## 0. Governing principles

1. **Nothing serves without an approved, safe review trail.** Only an
   `approved` creative inside an `active` campaign is eligible to serve. Anything
   else is never served and never billable.
2. **Self-hosted trust over arbitrary ad tags.** Reviewed, sandboxed assets beat
   raw third-party tags; raw `<script src=…>` is forbidden (ADR 0013).
3. **Published is immutable.** An approved/published creative is frozen; any
   change to its asset or `destination_url` creates a **new version** that must be
   **re-reviewed**.
4. **Four-eyes by default.** Approval requires the `review_creatives` capability;
   **self-approval is disallowed by default**, and any exception is audited.
5. **No silent deviation.** Any departure requires an ADR with security sign-off.

---

## 1. Review workflow (state machine)

```
        ┌─────────┐  submit   ┌───────────┐  start review  ┌────────────┐
        │  draft  │──────────►│ submitted │───────────────►│ in_review  │
        └─────────┘           └───────────┘                └─────┬──────┘
             ▲                                    approve │  │ request changes │ reject
             │ revise (new version)                       ▼  ▼                 ▼
             │                                     ┌──────────┐  ┌──────────────────┐  ┌──────────┐
             └─────────────────────────────────── │ approved │  │ changes_requested│  │ rejected │
                                                   └────┬─────┘  └──────────────────┘  └──────────┘
                                                        │ (eligible to serve when campaign active)
```

Rules:

- **Only `draft` / `changes_requested` creatives are editable.** Once
  `submitted`, the asset and `destination_url` are locked; status transitions
  only.
- **Approval gate:** transitioning to `approved` requires the `review_creatives`
  capability (ADR 0006). The submitter **MUST NOT** approve their own creative by
  default; an exception requires an audited override.
- **Re-review on change:** editing an approved creative is not allowed in place;
  it produces a **new `creative_version`** starting at `draft`. The old version
  stays as immutable history.
- **Rejection carries a reason** (`review_reason`); `changes_requested` returns it
  to the author for revision.
- Every review decision (who / when / from→to / reason) is an **audited event**.

---

## 2. Serving eligibility

A creative is **eligible to serve** only when **all** hold:

- `review_status = approved`
- its campaign `status = active` and within schedule (ADR 0012 terminology)
- (marketplace) its campaign is `funded` (billing rules)

If no eligible creative remains, Serve renders the **default / fallback** creative
(which itself must be `approved`) or an empty non-billable response — never an
unreviewed asset. Non-eligible creatives are **never billable** (measurement-spec
billable definition).

---

## 3. Acceptance rules per creative_type (ADR 0021)

| `creative_type` | MVP | Acceptance rules |
| --- | --- | --- |
| `image` | yes | Hosted file or HTTPS URL **allowlist**; format allowlist (e.g. PNG/JPEG/GIF/WebP); max dimensions/byte size enforced |
| `video` | Phase 2 | MP4/WebM, hosted; max size/duration; **no autoplay with sound by default**; poster image |
| `html5_bundle` | Phase 2 | Zip uploaded by an admin; structure + size limit validated; **malware scan must be `clean`**; served in **sandboxed iframe + strict CSP**; see §4 |
| `third_party_tag` | **no** | Raw `<script src=…>` **forbidden**; enabling requires a security-review ADR |

`destination_url` for any clickable creative: **https** (http only on localhost
dev), **registered on the creative**, never client-supplied — no open redirect
(ADR 0019).

---

## 4. HTML5 bundle sandbox policy (ADR 0021)

- **Malware/virus scan** on upload; only `scan_status = clean` may proceed to
  review. `flagged` bundles are blocked.
- Served in a **sandboxed `<iframe>`** with a strict **CSP**; the bundle:
  - **no `eval`** / no dynamic remote script injection,
  - **no top-level navigation** of the host page (`allow-top-navigation` denied);
    clicks go through the registered `/public/clicks/{click_token}` redirect,
  - **network egress allowlisted** (no arbitrary beacons / fingerprinting),
  - resource limits (bundle byte size, asset count) enforced.
- The host embed keeps a **single script entry point** (`serve.js`) and never
  `eval`s API responses (api-security-spec §2).

---

## 5. Content & anti-malvertising

- **No deceptive creatives:** no fake system/UI chrome, no auto-redirect, no
  forced download, no cross-publisher fingerprinting scripts (privacy N3).
- Operators are responsible for the **legality and rights** of the ad content
  they approve; Serve provides the review queue, scan, sandbox, and audit trail
  to make that defensible. Prohibited-category policy is operator-configurable.
- Reviewer **must** check the **landing page** (`destination_url`) target matches
  the declared creative and is safe (https, no malware redirect chain).

---

## 6. Audit & retention

- Submit, approve, reject, changes_requested, override (self-approval), version
  supersede, and scan results are **audited** (who/when/what), append-only
  (ADR 0006 audit events).
- Superseded creative versions and their review history are **retained** (not hard
  deleted) for traceability; if a version was ever served in marketplace mode its
  retention follows the billing-relevant regime (billing doc §7).

---

## 7. How this applies to every change

Any change touching creative types, the review workflow, sandbox/CSP, scanning,
`destination_url` handling, or serving eligibility **MUST**:

1. Be reviewed against this document and
   [`../review/creative-review.md`](../review/creative-review.md).
2. State its safety impact in the PR.
3. If it deviates, carry an ADR with security sign-off.

If unsure whether a change has creative-safety impact, **assume it does** and run
the checklist.

---

## Related

- [ADR 0013](../adr/0013-creative-sandbox-policy.md), [ADR 0020](../adr/0020-creative-review-workflow.md), [ADR 0021](../adr/0021-creative-acceptance-and-sandbox-safety.md), ADR 0006, ADR 0019
- [`api-security-spec.md`](./api-security-spec.md), [`measurement-spec.md`](./measurement-spec.md), [`privacy-and-ad-compliance.md`](./privacy-and-ad-compliance.md)
- [`../review/creative-review.md`](../review/creative-review.md)

Last updated: 2026-06-04
