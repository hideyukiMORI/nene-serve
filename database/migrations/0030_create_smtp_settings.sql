-- 0030 smtp_settings — per-tenant outbound SMTP configuration, managed in the
-- admin console. The password is stored ENCRYPTED AT REST (Support\Crypto,
-- libsodium); the key lives only in APP_ENCRYPTION_KEY (never in the DB).
-- Governed: FK RESTRICT; one row per organization; updated in place (no delete).
CREATE TABLE smtp_settings (
    organization_id    CHAR(36) NOT NULL,
    host               VARCHAR(255) NOT NULL,
    port               INT NOT NULL,
    username           VARCHAR(255) NOT NULL DEFAULT '',
    password_encrypted TEXT NULL,
    from_address       VARCHAR(255) NOT NULL,
    from_name          VARCHAR(255) NOT NULL DEFAULT '',
    encryption         ENUM('none', 'starttls', 'tls') NOT NULL DEFAULT 'starttls',
    updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (organization_id),
    CONSTRAINT fk_smtp_settings_org FOREIGN KEY (organization_id)
        REFERENCES organizations (id) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
