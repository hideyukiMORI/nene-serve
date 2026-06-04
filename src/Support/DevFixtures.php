<?php

declare(strict_types=1);

namespace NeneServe\Support;

use NeneServe\Tenant\InMemoryOrganizationRepository;
use NeneServe\Tenant\InMemoryUserRepository;
use NeneServe\Tenant\Organization;
use NeneServe\Tenant\Role;
use NeneServe\Tenant\User;

/**
 * Deterministic seed data for local `php -S` boot and tests, before the MySQL
 * stack and migrations are wired into the kernel (#12). Two tenants exercise
 * tenant isolation; one cross-tenant superadmin exercises the bypass.
 *
 * NOT for production — production injects the PDO repositories.
 */
final class DevFixtures
{
    public const PASSWORD = 'secret123';

    public static function organizations(): InMemoryOrganizationRepository
    {
        return new InMemoryOrganizationRepository([
            new Organization('org-acme', 'acme', 'Acme Media'),
            new Organization('org-globex', 'globex', 'Globex Publishing'),
        ]);
    }

    public static function users(): InMemoryUserRepository
    {
        $hash = password_hash(self::PASSWORD, PASSWORD_DEFAULT);

        return new InMemoryUserRepository([
            new User('user-acme-admin', 'org-acme', 'admin@acme.test', Role::OrgAdmin, $hash),
            new User('user-acme-analyst', 'org-acme', 'analyst@acme.test', Role::Analyst, $hash),
            new User('user-globex-admin', 'org-globex', 'admin@globex.test', Role::OrgAdmin, $hash),
            new User('user-root', 'org-acme', 'root@serve.test', Role::SuperAdmin, $hash),
        ]);
    }
}
