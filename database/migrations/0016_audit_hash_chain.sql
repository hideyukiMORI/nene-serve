-- 0016 tamper-evident audit chain (ADR 0022 §5). `seq` gives a stable global
-- order; `previous_hash`/`hash` form a per-tenant SHA-256 chain so any edit,
-- reorder, or deleted row is detectable. Append-only; never updated.
ALTER TABLE audit_events
    ADD COLUMN seq           BIGINT   NOT NULL AUTO_INCREMENT UNIQUE FIRST,
    ADD COLUMN previous_hash CHAR(64) NOT NULL DEFAULT '' AFTER occurred_at,
    ADD COLUMN hash          CHAR(64) NOT NULL DEFAULT '' AFTER previous_hash;
