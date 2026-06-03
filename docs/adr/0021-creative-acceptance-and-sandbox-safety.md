# ADR 0021: Creative Acceptance & Sandbox Safety Policy

## Status

accepted

## Context

ADR 0013 set the creative-type matrix and the principle that HTML5 ships as a
reviewed bundle, not raw script injection. To be defensible against
**malvertising / XSS / open redirect**, the per-type acceptance criteria and the
sandbox technical policy must be binding, not implied.

## Decision

1. **Per-type acceptance:**
   - `image` (MVP): hosted or HTTPS-allowlisted; format allowlist; max
     dimensions and byte size enforced.
   - `video` (Phase 2): MP4/WebM hosted; max size/duration; **no autoplay with
     sound by default**.
   - `html5_bundle` (Phase 2): admin-uploaded zip; structure + size validated;
     **malware scan must be `clean`** before review; rendered per §2.
   - `third_party_tag`: **forbidden**; enabling requires a security-review ADR.
2. **HTML5 sandbox policy:** rendered in a **sandboxed iframe + strict CSP**, with
   **no `eval`**, **no top-level navigation** (clicks go through the registered
   click redirect), **network egress allowlisted**, and resource limits enforced.
3. **Destination safety:** `destination_url` is **https** (localhost `http` only),
   **registered on the creative**, never client-supplied — no open redirect
   (ADR 0019).
4. **Malware scanning:** bundles are scanned on upload; `flagged` is blocked;
   only `clean` proceeds. Scan result is recorded and audited.
5. **No deceptive content:** no fake UI chrome, auto-redirect, forced download, or
   cross-publisher fingerprinting (privacy N3).
6. **Deviation gate:** loosening acceptance, the sandbox, or enabling third-party
   tags requires an ADR with security sign-off.

## Consequences

**Benefits**

- Concrete, testable acceptance criteria; HTML5 units cannot break out of the
  sandbox or relay an open redirect.
- A clear "no AdSense tag paste" answer for MVP, with a governed path to change.

**Costs**

- Requires a scanner integration and CSP/sandbox plumbing before HTML5 ships
  (Phase 2); operators cannot paste arbitrary tags.

## Related

- [`../explanation/creative-review-and-safety.md`](../explanation/creative-review-and-safety.md) (binding)
- [ADR 0020](0020-creative-review-workflow.md), ADR 0013, ADR 0019, ADR 0010
- [`../explanation/api-security-spec.md`](../explanation/api-security-spec.md), [`../explanation/privacy-and-ad-compliance.md`](../explanation/privacy-and-ad-compliance.md)
