-- SQLite mirror of billing_periods columns (final migration state).
CREATE TABLE billing_periods (
    id              TEXT NOT NULL PRIMARY KEY,
    organization_id TEXT NOT NULL,
    campaign_id     TEXT NOT NULL,
    period_start    TEXT NOT NULL,
    period_end      TEXT NOT NULL,
    status          TEXT NOT NULL DEFAULT 'open'
);
