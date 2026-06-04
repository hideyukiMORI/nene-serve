-- 0029 service_tokens — opaque, tenant-bound, scoped machine tokens for the
-- service surface (api-security §5, ADR 0018/0019). The raw secret is never
-- stored; only its SHA-256 hash. Governed: FK RESTRICT; revoke is an additive
-- tombstone (status → 'revoked' + revoked_at), never a physical delete (ADR 0022).
CREATE TABLE service_tokens (
    id              CHAR(36) NOT NULL,
    organization_id CHAR(36) NOT NULL,
    token_hash      CHAR(64) NOT NULL,
    scopes          JSON NOT NULL,
    status          ENUM('active', 'revoked') NOT NULL DEFAULT 'active',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revoked_at      DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_service_tokens_hash (token_hash),
    KEY idx_service_tokens_org (organization_id),
    CONSTRAINT fk_service_tokens_org FOREIGN KEY (organization_id)
        REFERENCES organizations (id) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
