-- 0014 serve_requests — one row per public serve attempt, for fill rate
-- (measurement-spec). `filled` = a non-fallback creative was returned. Aggregate
-- only; no visitor identifiers.
CREATE TABLE serve_requests (
    id              CHAR(32)  NOT NULL,
    organization_id CHAR(36)  NOT NULL,
    placement_id    CHAR(36)  NOT NULL,
    occurred_at     DATETIME  NOT NULL,
    filled          TINYINT(1) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_serve_requests_org_time (organization_id, occurred_at),
    KEY idx_serve_requests_placement (placement_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
