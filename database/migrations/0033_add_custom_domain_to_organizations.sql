-- 0033 custom-domain tenant resolution (ADR 0006). Optional: a tenant may point
-- its own domain (CNAME) at this install and be resolved by it in
-- `custom_domain` mode. Null = no custom domain (slug/subdomain/path/single
-- modes are unaffected). Unique so a domain maps to at most one organization.
ALTER TABLE organizations
    ADD COLUMN custom_domain VARCHAR(255) NULL AFTER slug,
    ADD UNIQUE KEY uq_organizations_custom_domain (custom_domain);
