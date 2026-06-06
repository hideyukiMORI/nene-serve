-- SQLite mirror of the clicks columns the repository reads/writes (final
-- migration state). Types are SQLite-portable. Production DDL is
-- database/migrations (MySQL).
CREATE TABLE clicks (
    id                  TEXT NOT NULL PRIMARY KEY,
    organization_id     TEXT NOT NULL,
    placement_id        TEXT NOT NULL,
    creative_id         TEXT NOT NULL,
    occurred_at         TEXT NOT NULL,
    country_code        TEXT NULL,
    non_billable_reason TEXT NULL
);
