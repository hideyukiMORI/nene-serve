-- SQLite mirror of campaigns columns (final migration state).
CREATE TABLE campaigns (
    id                        TEXT    NOT NULL PRIMARY KEY,
    organization_id           TEXT    NOT NULL,
    advertiser_id             TEXT    NOT NULL,
    name                      TEXT    NOT NULL,
    pricing_rule_id           TEXT    NOT NULL,
    budget_cents              INTEGER NOT NULL,
    status                    TEXT    NOT NULL DEFAULT 'draft',
    funding_status            TEXT    NOT NULL DEFAULT 'unfunded',
    pause_on_budget_exhausted INTEGER NOT NULL DEFAULT 1,
    archived_at               TEXT    NULL
);
