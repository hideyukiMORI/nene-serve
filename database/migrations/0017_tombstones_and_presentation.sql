-- 0017 lifecycle tombstones (ADR 0022 §3): "delete" is a state change, never a
-- physical row removal. Plus the first **presentation** table (user_preferences),
-- which — unlike governed tables — may be freely edited/deleted (ADR 0022 §1).
ALTER TABLE placements    ADD COLUMN archived_at  DATETIME NULL AFTER frequency_cap;
ALTER TABLE creatives     ADD COLUMN archived_at  DATETIME NULL AFTER scan_status;
ALTER TABLE users         ADD COLUMN disabled_at  DATETIME NULL AFTER status;
ALTER TABLE organizations ADD COLUMN disabled_at  DATETIME NULL AFTER status;

-- Presentation data (cosmetic, per-user UI state). Not governed: freely
-- editable/deletable, not audited. The application DB role MAY DELETE here.
CREATE TABLE user_preferences (
    user_id    CHAR(36) NOT NULL,
    locale     VARCHAR(16) NULL,
    theme      VARCHAR(32) NULL,
    layout     JSON NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    CONSTRAINT fk_user_preferences_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
