-- 0005 review trail on creatives (ADR 0020): who submitted (four-eyes) and the
-- reason attached to reject / changes_requested.
ALTER TABLE creatives
    ADD COLUMN submitted_by CHAR(36)      NULL AFTER version,
    ADD COLUMN review_reason VARCHAR(1024) NULL AFTER submitted_by;
