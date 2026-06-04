-- 0003 placements — ad slots (ADR 0006/0010). allowed_origins gates the public
-- surface; public_placement_key is a public (non-secret) identifier for serve.js.
CREATE TABLE placements (
    id                   CHAR(36)     NOT NULL,
    organization_id      CHAR(36)     NOT NULL,
    public_placement_key VARCHAR(64)  NOT NULL,
    allowed_origins      JSON         NOT NULL,
    status               ENUM('draft', 'active', 'paused', 'archived') NOT NULL DEFAULT 'draft',
    default_creative_id  CHAR(36)     NULL,
    created_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_placements_public_key (public_placement_key),
    KEY idx_placements_org (organization_id),
    CONSTRAINT fk_placements_org FOREIGN KEY (organization_id)
        REFERENCES organizations (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
