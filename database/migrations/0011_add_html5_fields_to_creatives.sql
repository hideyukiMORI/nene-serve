-- 0011 HTML5 bundle fields on creatives (ADR 0021 §4): bundle reference, byte
-- size, and malware scan status. Null for image/video. Only scan_status='clean'
-- may be submitted/served.
ALTER TABLE creatives
    ADD COLUMN bundle_id         VARCHAR(64) NULL AFTER duration_seconds,
    ADD COLUMN bundle_size_bytes INT         NULL AFTER bundle_id,
    ADD COLUMN scan_status       ENUM('pending', 'clean', 'flagged') NULL AFTER bundle_size_bytes;
