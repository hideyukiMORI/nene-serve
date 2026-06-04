-- 0023 spend_snapshots — versioned, tamper-evident substantiation of a billing
-- period's spend (billing §3.2/§3.3/§7). Append-only/immutable: re-deriving writes
-- a new version, never an overwrite. spent_cents is net integer money (no tax,
-- no float/DECIMAL); hash is SHA-256 over the substantiating fields. Retained for
-- the statutory period (#51). Governed table: FK RESTRICT (ADR 0022).
CREATE TABLE spend_snapshots (
    id                   CHAR(36) NOT NULL,
    organization_id      CHAR(36) NOT NULL,
    billing_period_id    CHAR(36) NOT NULL,
    version              INT      NOT NULL,
    billable_impressions BIGINT   NOT NULL,
    billable_clicks      BIGINT   NOT NULL,
    pricing_rule_id      CHAR(36) NOT NULL,
    pricing_rule_version INT      NOT NULL,
    spent_cents          BIGINT   NOT NULL,
    hash                 CHAR(64) NOT NULL,
    created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_spend_snapshots_period_version (organization_id, billing_period_id, version),
    CONSTRAINT fk_spend_snapshots_org FOREIGN KEY (organization_id)
        REFERENCES organizations (id) ON DELETE RESTRICT,
    CONSTRAINT fk_spend_snapshots_period FOREIGN KEY (billing_period_id)
        REFERENCES billing_periods (id) ON DELETE RESTRICT,
    CONSTRAINT fk_spend_snapshots_pricing_rule FOREIGN KEY (pricing_rule_id)
        REFERENCES pricing_rules (id) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
