-- SQLite mirror of public_tokens (migration 0035). ENUM/DATETIME → TEXT.
-- token_hash is the SHA-256 hex of the token; the raw token is never stored.
CREATE TABLE public_tokens (
    token_hash      TEXT NOT NULL PRIMARY KEY,
    kind            TEXT NOT NULL,
    organization_id TEXT NOT NULL,
    placement_id    TEXT NULL,
    creative_id     TEXT NOT NULL,
    destination_url TEXT NULL,
    expires_at      INTEGER NULL,
    recorded_at     INTEGER NULL,
    consumed_at     INTEGER NULL,
    created_at      TEXT NULL
);
