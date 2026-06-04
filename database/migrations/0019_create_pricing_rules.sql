-- 0019 pricing_rules — versioned, immutable rule converting billable units to net
-- money (billing §3.3 reproducibility, ADR 0015). rate_cents is an integer net
-- amount (no tax, no float/DECIMAL); a change is a new (name, version) row, never
-- an in-place edit. Append-only governed table.
CREATE TABLE pricing_rules (
    id              CHAR(36)     NOT NULL,
    organization_id CHAR(36)     NOT NULL,
    name            VARCHAR(128) NOT NULL,
    pricing_model   ENUM('cpm', 'cpc', 'flat') NOT NULL,
    rate_cents      BIGINT       NOT NULL,
    version         INT          NOT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pricing_rules_name_version (organization_id, name, version),
    CONSTRAINT fk_pricing_rules_org FOREIGN KEY (organization_id)
        REFERENCES organizations (id) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
