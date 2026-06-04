-- 0008 impressions — append-only events (measurement-spec, ADR 0012). Minimized
-- fields only: NO raw PII (privacy N6). visitor_bucket is hashed; page URL is
-- truncated; location is a country code. Retention per privacy §6.
CREATE TABLE impressions (
    id                  CHAR(32)     NOT NULL,
    organization_id     CHAR(36)     NOT NULL,
    placement_id        CHAR(36)     NOT NULL,
    creative_id         VARCHAR(64)  NOT NULL,
    occurred_at         DATETIME     NOT NULL,
    country_code        CHAR(2)      NULL,
    placement_page_url  VARCHAR(512) NULL,
    visitor_bucket      CHAR(32)     NULL,
    non_billable_reason ENUM('fallback', 'error', 'bot_filtered', 'opt_out', 'unfunded') NULL,
    PRIMARY KEY (id),
    KEY idx_impressions_org_time (organization_id, occurred_at),
    KEY idx_impressions_placement (placement_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
