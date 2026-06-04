-- 0026 deal_opportunities — record of a campaign handed to NeNe Deal (sibling map
-- Phase 4, ADR 0002). Idempotent on external_reference (UNIQUE): no duplicate
-- opportunity. amount_cents is net (no tax). Governed: FK RESTRICT; only
-- status/opportunity_id advance.
CREATE TABLE deal_opportunities (
    id                 CHAR(36)     NOT NULL,
    organization_id    CHAR(36)     NOT NULL,
    campaign_id        CHAR(36)     NOT NULL,
    external_reference VARCHAR(128) NOT NULL,
    amount_cents       BIGINT       NOT NULL,
    status             ENUM('pending', 'sent', 'failed') NOT NULL,
    opportunity_id     VARCHAR(64)  NULL,
    created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_deal_opportunities_external_ref (organization_id, external_reference),
    CONSTRAINT fk_deal_opportunities_org FOREIGN KEY (organization_id)
        REFERENCES organizations (id) ON DELETE RESTRICT,
    CONSTRAINT fk_deal_opportunities_campaign FOREIGN KEY (campaign_id)
        REFERENCES campaigns (id) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
