-- 0015 governed-table foreign keys: ON DELETE CASCADE → RESTRICT (ADR 0022 §6,
-- audit-and-data-integrity §3). A tenant (or any parent) can no longer be
-- physically removed together with its users, placements, creatives, or — most
-- importantly — its audit trail. Org lifecycle is disable/archive, never delete.
--
-- Drop and re-add are separate statements per table: MySQL rejects reusing a
-- foreign-key name within a single ALTER (error 1826).
ALTER TABLE users DROP FOREIGN KEY fk_users_org;
ALTER TABLE users ADD CONSTRAINT fk_users_org FOREIGN KEY (organization_id)
    REFERENCES organizations (id) ON DELETE RESTRICT;

ALTER TABLE placements DROP FOREIGN KEY fk_placements_org;
ALTER TABLE placements ADD CONSTRAINT fk_placements_org FOREIGN KEY (organization_id)
    REFERENCES organizations (id) ON DELETE RESTRICT;

ALTER TABLE creatives DROP FOREIGN KEY fk_creatives_org;
ALTER TABLE creatives ADD CONSTRAINT fk_creatives_org FOREIGN KEY (organization_id)
    REFERENCES organizations (id) ON DELETE RESTRICT;

ALTER TABLE audit_events DROP FOREIGN KEY fk_audit_org;
ALTER TABLE audit_events ADD CONSTRAINT fk_audit_org FOREIGN KEY (organization_id)
    REFERENCES organizations (id) ON DELETE RESTRICT;
