-- 0013 consent + erasure on impressions (privacy ADR 0017 §3, §5). consent_state
-- records the decision in effect; erased_at is an additive DSR tombstone (the row
-- and its count are retained, the visitor link is forgotten — never a count edit).
ALTER TABLE impressions
    ADD COLUMN consent_state ENUM('granted', 'denied', 'unknown') NULL AFTER non_billable_reason,
    ADD COLUMN erased_at     DATETIME NULL AFTER consent_state,
    ADD KEY idx_impressions_visitor (organization_id, visitor_bucket);
