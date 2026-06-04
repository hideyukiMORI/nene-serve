<?php

declare(strict_types=1);

namespace NeneServe\Tenant;

/**
 * Tenant roles (ADR 0006). `superadmin` is the only cross-tenant role; all
 * others are confined to their resolved organization.
 */
enum Role: string
{
    case SuperAdmin = 'superadmin';
    case OrgAdmin = 'org_admin';
    case Editor = 'editor';
    case Analyst = 'analyst';

    /**
     * Capabilities granted to this role. `superadmin` implicitly holds every
     * capability (see {@see self::can()}); the matrix below is the source of
     * truth for the bounded roles and mirrors terminology.md.
     *
     * @return list<Capability>
     */
    public function capabilities(): array
    {
        return match ($this) {
            self::SuperAdmin => Capability::cases(),
            self::OrgAdmin => [
                Capability::ViewUsers,
                Capability::ManageUsers,
                Capability::ViewMetrics,
                Capability::ManageSettings,
                Capability::ReviewCreatives,
            ],
            self::Editor => [
                Capability::ViewMetrics,
            ],
            self::Analyst => [
                Capability::ViewMetrics,
            ],
        };
    }

    public function can(Capability $capability): bool
    {
        return $this === self::SuperAdmin
            || in_array($capability, $this->capabilities(), true);
    }

    public function isCrossTenant(): bool
    {
        return $this === self::SuperAdmin;
    }
}
