-- SQLite mirror of the placements columns the repository reads/writes (final
-- migration state). Types are SQLite-portable: ENUM/JSON/DATETIME → TEXT,
-- booleans → INTEGER. Production DDL is database/migrations (MySQL).
CREATE TABLE placements (
    id                   TEXT    NOT NULL PRIMARY KEY,
    organization_id      TEXT    NOT NULL,
    public_placement_key TEXT    NOT NULL UNIQUE,
    allowed_origins      TEXT    NOT NULL,
    status               TEXT    NOT NULL DEFAULT 'draft',
    default_creative_id  TEXT    NULL,
    measurement_enabled  INTEGER NOT NULL DEFAULT 1,
    frequency_cap        INTEGER NULL,
    archived_at          TEXT    NULL
);
