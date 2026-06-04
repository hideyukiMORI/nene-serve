-- 0007 per-placement measurement opt-out (privacy P2, ADR 0017). When false the
-- creative still serves (essential) but no tracking beacons are counted.
ALTER TABLE placements
    ADD COLUMN measurement_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER default_creative_id;
