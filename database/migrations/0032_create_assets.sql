-- 0032 assets — uploaded creative media (image/video). Bytes live in object
-- storage (Storage\*); this table is the governed metadata index. The public
-- serve route streams by opaque id with a fixed Content-Type (never executed).
-- Governed: FK RESTRICT; append-only metadata (no update/delete by the app).
CREATE TABLE assets (
    id              CHAR(36) NOT NULL,
    organization_id CHAR(36) NOT NULL,
    kind            ENUM('image', 'video') NOT NULL,
    content_type    VARCHAR(100) NOT NULL,
    byte_size       INT NOT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_assets_org (organization_id),
    CONSTRAINT fk_assets_org FOREIGN KEY (organization_id)
        REFERENCES organizations (id) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
