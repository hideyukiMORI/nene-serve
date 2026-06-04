-- 0004 creatives — reviewable assets (ADR 0020/0021). Only review_status='approved'
-- ever serves; destination_url is the sole registered redirect target (ADR 0019).
-- Approved versions are immutable; an edit creates a new version (#13).
CREATE TABLE creatives (
    id              CHAR(36)     NOT NULL,
    organization_id CHAR(36)     NOT NULL,
    type            ENUM('image', 'video', 'html5_bundle') NOT NULL,
    review_status   ENUM('draft', 'submitted', 'in_review', 'approved', 'rejected', 'changes_requested')
                        NOT NULL DEFAULT 'draft',
    destination_url VARCHAR(2048) NOT NULL,
    asset_url       VARCHAR(2048) NULL,
    width           INT          NULL,
    height          INT          NULL,
    version         INT          NOT NULL DEFAULT 1,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_creatives_org (organization_id),
    CONSTRAINT fk_creatives_org FOREIGN KEY (organization_id)
        REFERENCES organizations (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
