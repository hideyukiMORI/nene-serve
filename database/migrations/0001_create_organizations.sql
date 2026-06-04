-- 0001 organizations — tenant root (ADR 0006). organization_id scopes all data.
CREATE TABLE organizations (
    id             CHAR(36)     NOT NULL,
    slug           VARCHAR(64)  NOT NULL,
    name           VARCHAR(255) NOT NULL,
    default_locale VARCHAR(16)  NOT NULL DEFAULT 'en',
    status         ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_organizations_slug (slug)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
