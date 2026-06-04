-- 0012 per-visitor frequency cap (measurement-spec, privacy ADR 0017). Max
-- impressions per consent-gated visitor_bucket per day; null = uncapped. Caps are
-- only applied when consent permits a visitor bucket (no consent → no cap/track).
ALTER TABLE placements
    ADD COLUMN frequency_cap INT NULL AFTER measurement_enabled;
