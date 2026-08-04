-- SQLite mirror of rate_limit_counters (migration 0034). Upserted in place;
-- counter_key is the SHA-256 hex of the middleware key, never the raw address.
CREATE TABLE rate_limit_counters (
    counter_key     TEXT    NOT NULL PRIMARY KEY,
    hit_count       INTEGER NOT NULL,
    window_reset_at INTEGER NOT NULL
);
