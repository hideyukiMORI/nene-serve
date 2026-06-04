-- 0010 video fields on creatives (ADR 0021 §3): poster image and duration for
-- video creatives; null for image/html5.
ALTER TABLE creatives
    ADD COLUMN poster_url       VARCHAR(2048) NULL AFTER review_reason,
    ADD COLUMN duration_seconds INT          NULL AFTER poster_url;
