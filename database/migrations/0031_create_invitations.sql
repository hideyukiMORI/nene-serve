-- 0031 invitations — single-use, expiring tokens for operator onboarding. The
-- admin creates a user (no usable password) and an invitation; the invitee sets
-- their password via the emailed link. Only the SHA-256 token hash is stored
-- (never the raw token). Governed: FK RESTRICT; status advances pending→accepted.
CREATE TABLE invitations (
    id              CHAR(36) NOT NULL,
    organization_id CHAR(36) NOT NULL,
    user_id         CHAR(36) NOT NULL,
    token_hash      CHAR(64) NOT NULL,
    status          ENUM('pending', 'accepted') NOT NULL DEFAULT 'pending',
    expires_at      DATETIME NOT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    accepted_at     DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_invitations_token (token_hash),
    KEY idx_invitations_org (organization_id),
    CONSTRAINT fk_invitations_org FOREIGN KEY (organization_id)
        REFERENCES organizations (id) ON DELETE RESTRICT,
    CONSTRAINT fk_invitations_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
