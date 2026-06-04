-- 0006 audit_events — append-only review/decision trail (ADR 0006, creative-review §6).
-- Never updated or deleted; superseded creative versions retained for traceability.
CREATE TABLE audit_events (
    id              CHAR(16)     NOT NULL,
    organization_id CHAR(36)     NOT NULL,
    actor_user_id   CHAR(36)     NOT NULL,
    action          VARCHAR(64)  NOT NULL,
    subject_type    VARCHAR(32)  NOT NULL,
    subject_id      VARCHAR(64)  NOT NULL,
    metadata        JSON         NOT NULL,
    occurred_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_subject (organization_id, subject_type, subject_id),
    KEY idx_audit_org_time (organization_id, occurred_at),
    CONSTRAINT fk_audit_org FOREIGN KEY (organization_id)
        REFERENCES organizations (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
