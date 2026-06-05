-- SQLite mirror of the creatives columns the repository reads/writes (final
-- migration state). Production DDL is database/migrations (MySQL).
CREATE TABLE creatives (
    id               TEXT    NOT NULL PRIMARY KEY,
    organization_id  TEXT    NOT NULL,
    type             TEXT    NOT NULL,
    review_status    TEXT    NOT NULL DEFAULT 'draft',
    destination_url  TEXT    NOT NULL,
    asset_url        TEXT    NULL,
    width            INTEGER NULL,
    height           INTEGER NULL,
    version          INTEGER NOT NULL DEFAULT 1,
    submitted_by     TEXT    NULL,
    review_reason    TEXT    NULL,
    poster_url       TEXT    NULL,
    duration_seconds INTEGER NULL,
    bundle_id        TEXT    NULL,
    bundle_size_bytes INTEGER NULL,
    scan_status      TEXT    NULL,
    campaign_id      TEXT    NULL
);
