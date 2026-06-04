-- 0009 clicks — append-only redirect events (measurement-spec, ADR 0012).
-- Minimized fields only; no raw PII (privacy N6). Retention per privacy §6.
CREATE TABLE clicks (
    id                  CHAR(32)     NOT NULL,
    organization_id     CHAR(36)     NOT NULL,
    placement_id        CHAR(36)     NOT NULL,
    creative_id         VARCHAR(64)  NOT NULL,
    occurred_at         DATETIME     NOT NULL,
    country_code        CHAR(2)      NULL,
    non_billable_reason ENUM('fallback', 'error', 'bot_filtered', 'opt_out', 'unfunded') NULL,
    PRIMARY KEY (id),
    KEY idx_clicks_org_time (organization_id, occurred_at),
    KEY idx_clicks_placement (placement_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
