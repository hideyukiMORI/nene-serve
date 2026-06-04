-- 0021 link creatives to a marketplace campaign (Phase 3). Null = no advertiser
-- money behind the creative (Phase 1/2 self-serve). FK RESTRICT (ADR 0022).
ALTER TABLE creatives
    ADD COLUMN campaign_id CHAR(36) NULL AFTER scan_status,
    ADD KEY idx_creatives_campaign (campaign_id),
    ADD CONSTRAINT fk_creatives_campaign FOREIGN KEY (campaign_id)
        REFERENCES campaigns (id) ON DELETE RESTRICT;
