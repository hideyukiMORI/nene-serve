-- SQLite mirror of spend_snapshots columns (final migration state). Append-only.
CREATE TABLE spend_snapshots (
    id                   TEXT    NOT NULL PRIMARY KEY,
    organization_id      TEXT    NOT NULL,
    billing_period_id    TEXT    NOT NULL,
    version              INTEGER NOT NULL,
    billable_impressions INTEGER NOT NULL,
    billable_clicks      INTEGER NOT NULL,
    pricing_rule_id      TEXT    NOT NULL,
    pricing_rule_version INTEGER NOT NULL,
    spent_cents          INTEGER NOT NULL,
    hash                 TEXT    NOT NULL,
    created_at           TEXT    NOT NULL
);
