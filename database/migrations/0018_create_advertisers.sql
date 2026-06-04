-- 0018 advertisers — marketplace identity (Phase 3, ADR 0014). Operational only;
-- the advertiser's money system of record is NeNe Invoice. Governed table: FK
-- RESTRICT, disable tombstone (ADR 0022).
CREATE TABLE advertisers (
    id                CHAR(36)     NOT NULL,
    organization_id   CHAR(36)     NOT NULL,
    name              VARCHAR(255) NOT NULL,
    status            ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
    invoice_client_id VARCHAR(64)  NULL,
    disabled_at       DATETIME     NULL,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_advertisers_org (organization_id),
    CONSTRAINT fk_advertisers_org FOREIGN KEY (organization_id)
        REFERENCES organizations (id) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
