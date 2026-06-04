-- 0002 users — operator accounts, each bound to one organization (ADR 0006).
-- email is unique per tenant, not globally (the same address may exist in two orgs).
CREATE TABLE users (
    id              CHAR(36)     NOT NULL,
    organization_id CHAR(36)     NOT NULL,
    email           VARCHAR(255) NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    role            ENUM('superadmin', 'org_admin', 'editor', 'analyst') NOT NULL,
    status          ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_org_email (organization_id, email),
    KEY idx_users_org (organization_id),
    CONSTRAINT fk_users_org FOREIGN KEY (organization_id)
        REFERENCES organizations (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
