-- SQLite mirror of the impressions columns the repository reads/writes (final
-- migration state). Types are SQLite-portable: ENUM/DATETIME → TEXT. Production
-- DDL is database/migrations (MySQL).
CREATE TABLE impressions (
    id                  TEXT NOT NULL PRIMARY KEY,
    organization_id     TEXT NOT NULL,
    placement_id        TEXT NOT NULL,
    creative_id         TEXT NOT NULL,
    occurred_at         TEXT NOT NULL,
    country_code        TEXT NULL,
    placement_page_url  TEXT NULL,
    visitor_bucket      TEXT NULL,
    non_billable_reason TEXT NULL,
    consent_state       TEXT NULL,
    erased_at           TEXT NULL
);
