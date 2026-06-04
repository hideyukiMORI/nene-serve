<?php

declare(strict_types=1);

namespace NeneServe\Tests\Tenant;

use NeneServe\Support\DevFixtures;
use PHPUnit\Framework\TestCase;

/**
 * Repository-level proof that the scoped lookups never cross tenants (ADR 0006).
 */
final class TenantIsolationTest extends TestCase
{
    public function testScopedLookupCannotReachAnotherTenant(): void
    {
        $users = DevFixtures::users();

        // A globex user is invisible when scoped to acme.
        self::assertNull($users->findByIdInOrganization('user-globex-admin', 'org-acme'));
        self::assertNotNull($users->findByIdInOrganization('user-globex-admin', 'org-globex'));
    }

    public function testListByOrganizationExcludesOtherTenants(): void
    {
        $emails = array_map(
            static fn ($u) => $u->email,
            DevFixtures::users()->listByOrganization('org-globex'),
        );

        self::assertSame(['admin@globex.test'], $emails);
    }
}
