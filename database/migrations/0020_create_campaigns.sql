-- 0020 campaigns — advertiser-funded delivery with a budget + versioned pricing
-- rule (Phase 3, billing §3.1). budget_cents is net integer money (no tax, no
-- float/DECIMAL). Governed table: FK RESTRICT, archive tombstone (ADR 0022).
CREATE TABLE campaigns (
    id                        CHAR(36)     NOT NULL,
    organization_id           CHAR(36)     NOT NULL,
    advertiser_id             CHAR(36)     NOT NULL,
    name                      VARCHAR(255) NOT NULL,
    pricing_rule_id           CHAR(36)     NOT NULL,
    budget_cents              BIGINT       NOT NULL,
    status                    ENUM('draft', 'active', 'paused', 'archived') NOT NULL DEFAULT 'draft',
    funding_status            ENUM('unfunded', 'funded') NOT NULL DEFAULT 'unfunded',
    pause_on_budget_exhausted TINYINT(1)   NOT NULL DEFAULT 1,
    archived_at               DATETIME     NULL,
    created_at                DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_campaigns_org (organization_id),
    CONSTRAINT fk_campaigns_org FOREIGN KEY (organization_id)
        REFERENCES organizations (id) ON DELETE RESTRICT,
    CONSTRAINT fk_campaigns_advertiser FOREIGN KEY (advertiser_id)
        REFERENCES advertisers (id) ON DELETE RESTRICT,
    CONSTRAINT fk_campaigns_pricing_rule FOREIGN KEY (pricing_rule_id)
        REFERENCES pricing_rules (id) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
