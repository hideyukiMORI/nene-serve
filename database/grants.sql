-- NeNe Serve — application DB role least-privilege grants (ADR 0022 §6).
--
-- The application role gets SELECT/INSERT/UPDATE on **governed** tables and NO
-- DELETE / TRUNCATE / DROP, so no bug or manual command can physically remove
-- governed data (incl. the audit trail). `audit_events` is append-only (no
-- UPDATE). Only **presentation** tables grant DELETE. Destructive maintenance
-- (test resets, governed retention purges) uses a separate privileged role.
--
-- Recreates the application user with exactly these privileges (deterministic;
-- avoids REVOKE-matching pitfalls). The dev password matches docker-compose;
-- production manages credentials out of band. Re-run when a governed table is
-- added — the audit-and-data-integrity self-review requires it.
--
-- Usage (as admin): mysql -uroot -p nene_serve < database/grants.sql

DROP USER IF EXISTS 'nene'@'%';
CREATE USER 'nene'@'%' IDENTIFIED BY 'nene';

-- Governed tables: read + append + update only (no DELETE/TRUNCATE).
GRANT SELECT, INSERT, UPDATE ON nene_serve.organizations  TO 'nene'@'%';
GRANT SELECT, INSERT, UPDATE ON nene_serve.users          TO 'nene'@'%';
GRANT SELECT, INSERT, UPDATE ON nene_serve.placements     TO 'nene'@'%';
GRANT SELECT, INSERT, UPDATE ON nene_serve.creatives      TO 'nene'@'%';
GRANT SELECT, INSERT, UPDATE ON nene_serve.impressions    TO 'nene'@'%';
GRANT SELECT, INSERT, UPDATE ON nene_serve.clicks         TO 'nene'@'%';
GRANT SELECT, INSERT, UPDATE ON nene_serve.serve_requests TO 'nene'@'%';
GRANT SELECT, INSERT         ON nene_serve.conversions    TO 'nene'@'%'; -- append-only
GRANT SELECT, INSERT, UPDATE ON nene_serve.advertisers    TO 'nene'@'%';
GRANT SELECT, INSERT, UPDATE ON nene_serve.campaigns        TO 'nene'@'%';
GRANT SELECT, INSERT, UPDATE ON nene_serve.billing_periods  TO 'nene'@'%'; -- status advances
GRANT SELECT, INSERT, UPDATE ON nene_serve.invoice_handoffs TO 'nene'@'%'; -- status/payment id fill-in only
GRANT SELECT, INSERT, UPDATE ON nene_serve.deal_opportunities TO 'nene'@'%'; -- status/opportunity id fill-in only
GRANT SELECT, INSERT         ON nene_serve.pricing_rules    TO 'nene'@'%'; -- versioned, immutable
GRANT SELECT, INSERT         ON nene_serve.spend_snapshots  TO 'nene'@'%'; -- append-only, immutable
GRANT SELECT, INSERT         ON nene_serve.audit_events   TO 'nene'@'%'; -- append-only

GRANT SELECT, INSERT, UPDATE ON nene_serve.legal_holds     TO 'nene'@'%';
GRANT SELECT, INSERT, UPDATE ON nene_serve.change_plans    TO 'nene'@'%'; -- status advances
GRANT SELECT, INSERT, UPDATE ON nene_serve.service_tokens  TO 'nene'@'%'; -- revoke = status tombstone, no delete
GRANT SELECT, INSERT, UPDATE ON nene_serve.smtp_settings   TO 'nene'@'%'; -- upsert via INSERT..ON DUPLICATE KEY UPDATE (no delete)
GRANT SELECT, INSERT, UPDATE ON nene_serve.invitations    TO 'nene'@'%'; -- status advances pending->accepted

-- Presentation data: cosmetic UI state may be deleted.
GRANT SELECT, INSERT, UPDATE, DELETE ON nene_serve.user_preferences TO 'nene'@'%';

-- NOTE: the app role above has NO DELETE on governed tables (ADR 0022 §6). The
-- governed retention purge (scripts/purge-retention.php, billing §7) runs under a
-- SEPARATE privileged role that holds DELETE on impressions/clicks — never this one.

FLUSH PRIVILEGES;
