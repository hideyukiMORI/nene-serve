-- 0025 legal_holds — suspend retention purges for a tenant (billing §7, ADR
-- 0022 §7). While any hold is active (released_at IS NULL) nothing is purged.
-- Releases are tombstoned, never deleted. Governed table: FK RESTRICT.
CREATE TABLE legal_holds (
    id              CHAR(36)     NOT NULL,
    organization_id CHAR(36)     NOT NULL,
    reason          VARCHAR(512) NOT NULL,
    placed_at       DATETIME     NOT NULL,
    released_at     DATETIME     NULL,
    PRIMARY KEY (id),
    KEY idx_legal_holds_active (organization_id, released_at),
    CONSTRAINT fk_legal_holds_org FOREIGN KEY (organization_id)
        REFERENCES organizations (id) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
