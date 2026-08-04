-- 0035 public_tokens — the opaque public-surface tokens (ADR 0019), shared
-- across processes and hosts so the serve → beacon → click flow survives any
-- routing (#207). Replaces the single-host file store; the in-request store it
-- descends from could not span two requests at all.
--
-- Three kinds in one table because they share a shape (opaque handle → target,
-- optional expiry, optional one-way consumption):
--   impression — idempotent. `recorded_at` flips once; a replay reports
--                "already recorded" and never inflates a count (ADR 0015)
--   click      — single-use with a short TTL. `consumed_at` flips once, under a
--                conditional UPDATE, so two concurrent redemptions cannot both
--                win. Expired/used/unknown are indistinguishable to the caller
--   frame      — reusable within its TTL; no consumption. Keeps the internal
--                creative id out of the sandbox URL (api-security §2)
--
-- SECURITY: `token_hash` is the SHA-256 of the token, never the token itself —
-- the same rule `service_tokens` follows ("the raw secret is never stored").
-- Read access to this table therefore does not hand over usable click tokens.
--
-- NOT governed data (ADR 0022): no tenant FK, no audit, no retention regime.
-- These are short-lived handles, not business records. They are still written
-- INSERT/UPDATE-only, because the app DB role holds no DELETE; consumption is a
-- one-way flip, never a removal. Pruning expired rows is a privileged
-- maintenance task (see #205 for the sibling case).
CREATE TABLE public_tokens (
    token_hash      CHAR(64) NOT NULL,
    kind            ENUM('impression', 'click', 'frame') NOT NULL,
    organization_id CHAR(36) NOT NULL,
    placement_id    CHAR(36) NULL,
    creative_id     CHAR(36) NOT NULL,
    destination_url TEXT NULL,
    expires_at      BIGINT NULL,
    recorded_at     BIGINT NULL,
    consumed_at     BIGINT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (token_hash),
    KEY idx_public_tokens_expires (expires_at)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
