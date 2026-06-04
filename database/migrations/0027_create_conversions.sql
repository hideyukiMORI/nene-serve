-- 0027 conversions — append-only conversions attributed to delivery (ADR 0009
-- allowed integration: Concierge logs a conversion against a placement). This is
-- a MEASUREMENT event, NOT a Contact submission (no `submission` table, no inbox,
-- no PII). Minimized fields only; aggregated reporting.
CREATE TABLE conversions (
    id              CHAR(32)    NOT NULL,
    organization_id CHAR(36)    NOT NULL,
    placement_id    CHAR(36)    NOT NULL,
    creative_id     VARCHAR(64) NULL,
    occurred_at     DATETIME    NOT NULL,
    country_code    CHAR(2)     NULL,
    PRIMARY KEY (id),
    KEY idx_conversions_org_time (organization_id, occurred_at),
    KEY idx_conversions_placement (placement_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
