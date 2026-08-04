-- 0034 rate_limit_counters — fixed-window counters for the public-surface
-- throttle (ADR 0019 §5, api-security §63). Shared state: one row per rate limit
-- key, updated in place, so the limit is enforced across PHP-FPM workers and
-- hosts. The in-memory store it replaces could not trip at all (#199).
--
-- NOT governed data (ADR 0022): no tenant FK, no audit, no retention regime —
-- these are ephemeral abuse-control counters, not business records. They are
-- nonetheless written with the same INSERT/UPDATE-only shape as everything else,
-- because the app DB role holds no DELETE. Pruning rows whose window has long
-- expired is a privileged maintenance task, never an application path.
--
-- PRIVACY (ADR 0016/0017): `counter_key` is the SHA-256 hex of the middleware's
-- key, which by default contains the client IP. The raw key is never stored, so
-- no address is at rest here. Row count grows with distinct clients, not with
-- request volume, because each key is reused in place.
CREATE TABLE rate_limit_counters (
    counter_key     CHAR(64) NOT NULL,
    hit_count       INT NOT NULL,
    window_reset_at BIGINT NOT NULL,
    PRIMARY KEY (counter_key),
    KEY idx_rate_limit_counters_reset (window_reset_at)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
