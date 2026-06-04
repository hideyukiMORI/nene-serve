-- 0022 billing_periods — a campaign's billing window (billing §3.2). Status
-- advances open → closed → reconciled → handed_off; closed figures (the snapshot)
-- are immutable. Governed table: FK RESTRICT (ADR 0022).
CREATE TABLE billing_periods (
    id              CHAR(36) NOT NULL,
    organization_id CHAR(36) NOT NULL,
    campaign_id     CHAR(36) NOT NULL,
    period_start    DATE     NOT NULL,
    period_end      DATE     NOT NULL,
    status          ENUM('open', 'closed', 'reconciled', 'handed_off') NOT NULL DEFAULT 'open',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_billing_periods_campaign (organization_id, campaign_id),
    CONSTRAINT fk_billing_periods_org FOREIGN KEY (organization_id)
        REFERENCES organizations (id) ON DELETE RESTRICT,
    CONSTRAINT fk_billing_periods_campaign FOREIGN KEY (campaign_id)
        REFERENCES campaigns (id) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
