# Creative Review & Sandbox Safety Self-Review

**Binding.** Use for **any** change touching creative types, the review workflow,
sandbox/CSP, scanning, `destination_url` handling, or serving eligibility. If
unsure whether a change has creative-safety impact, **assume it does** and run
this list.

Source of truth:
[`../explanation/creative-review-and-safety.md`](../explanation/creative-review-and-safety.md).
Do not delete items to pass. Mark `N/A` only when genuinely not applicable.

## Checklist

- [ ] Change reviewed against `creative-review-and-safety.md`; safety impact stated in the PR.
- [ ] Only `approved` creatives in an `active` (marketplace: `funded`) campaign are eligible to serve; non-eligible never served and never billable.
- [ ] Review state machine respected: `draft → submitted → in_review → approved | rejected | changes_requested`; only `draft`/`changes_requested` editable.
- [ ] Approval requires `review_creatives` capability; **self-approval disallowed by default** (override audited) (ADR 0020).
- [ ] Editing an approved creative creates a **new version** requiring re-review; prior version retained immutable.
- [ ] Per-type acceptance enforced (image format/dimension allowlist; video no autoplay-with-sound; html5 zip validated) (ADR 0021).
- [ ] `html5_bundle` **malware scan = clean** before review; `flagged` blocked.
- [ ] HTML5 served in **sandboxed iframe + strict CSP**: no `eval`, no top-navigation, egress allowlisted, resource limits.
- [ ] `third_party_tag` remains **forbidden** (no raw `<script src>`); any enablement carries a security-review ADR.
- [ ] `destination_url` https + registered + not client-supplied — no open redirect (ADR 0019).
- [ ] No deceptive content (fake UI, auto-redirect, forced download, cross-publisher fingerprinting).
- [ ] Review decisions and scan results **audited** (who/when/what); superseded versions retained.
- [ ] New review states / capability / Problem Details slugs registered in `terminology.md`.
- [ ] Any deviation carries an **ADR with security sign-off**.
