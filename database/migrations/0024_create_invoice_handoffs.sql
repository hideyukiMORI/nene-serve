-- 0024 invoice_handoffs — reconciliation + handoff record per closed billing
-- period (billing §3.4/§3.5). Captures events → billable_units → amount →
-- external_reference. Idempotent on external_reference (UNIQUE): a retry never
-- double-charges. amount_cents is net integer money (no tax). Governed: FK
-- RESTRICT (ADR 0022); amounts/units stable, only status/payment id advance.
CREATE TABLE invoice_handoffs (
    id                   CHAR(36) NOT NULL,
    organization_id      CHAR(36) NOT NULL,
    billing_period_id    CHAR(36) NOT NULL,
    external_reference   VARCHAR(128) NOT NULL,
    billable_impressions BIGINT   NOT NULL,
    billable_clicks      BIGINT   NOT NULL,
    pricing_rule_version INT      NOT NULL,
    amount_cents         BIGINT   NOT NULL,
    reconciliation_status ENUM('reconciled', 'discrepancy') NOT NULL,
    status               ENUM('pending', 'handed_off', 'failed') NOT NULL,
    invoice_payment_id   VARCHAR(64) NULL,
    created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_invoice_handoffs_external_ref (organization_id, external_reference),
    CONSTRAINT fk_invoice_handoffs_org FOREIGN KEY (organization_id)
        REFERENCES organizations (id) ON DELETE RESTRICT,
    CONSTRAINT fk_invoice_handoffs_period FOREIGN KEY (billing_period_id)
        REFERENCES billing_periods (id) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
