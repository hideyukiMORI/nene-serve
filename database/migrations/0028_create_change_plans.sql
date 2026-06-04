-- 0028 change_plans — proposed delivery-plan changes from MCP/automation
-- (api-security §5, ADR 0018). A write is a PLAN that must be explicitly applied
-- (the id is the confirmation token); propose + apply are audited. Governed: FK
-- RESTRICT; only status advances (proposed → applied).
CREATE TABLE change_plans (
    id              CHAR(36) NOT NULL,
    organization_id CHAR(36) NOT NULL,
    placement_id    CHAR(36) NOT NULL,
    new_creative_id VARCHAR(64) NOT NULL,
    status          ENUM('proposed', 'applied') NOT NULL DEFAULT 'proposed',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_change_plans_org (organization_id),
    CONSTRAINT fk_change_plans_org FOREIGN KEY (organization_id)
        REFERENCES organizations (id) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
