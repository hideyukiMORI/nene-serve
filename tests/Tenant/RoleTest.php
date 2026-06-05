<?php

declare(strict_types=1);

namespace NeneServe\Tests\Tenant;

use NeneServe\Tenant\Capability;
use NeneServe\Tenant\Role;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Role → capability matrix (ADR 0006). superadmin holds everything implicitly;
 * the bounded roles are pinned positively (what they CAN do) and negatively
 * (what they must NOT) so a widened grant can't slip through unnoticed.
 */
final class RoleTest extends TestCase
{
    public function testSuperAdminHoldsEveryCapability(): void
    {
        foreach (Capability::cases() as $capability) {
            self::assertTrue(Role::SuperAdmin->can($capability), $capability->value);
        }
        self::assertSame(Capability::cases(), Role::SuperAdmin->capabilities());
    }

    public function testOnlySuperAdminIsCrossTenant(): void
    {
        self::assertTrue(Role::SuperAdmin->isCrossTenant());
        self::assertFalse(Role::OrgAdmin->isCrossTenant());
        self::assertFalse(Role::Editor->isCrossTenant());
        self::assertFalse(Role::Analyst->isCrossTenant());
    }

    public function testOrgAdminHasEveryCapabilityExceptNoneMissing(): void
    {
        // org_admin holds all bounded capabilities except cross-tenant powers,
        // which are expressed via isCrossTenant rather than a capability.
        foreach (Capability::cases() as $capability) {
            self::assertTrue(Role::OrgAdmin->can($capability), $capability->value);
        }
    }

    public function testEditorCanManageContentButNotUsersOrSettings(): void
    {
        self::assertTrue(Role::Editor->can(Capability::ManagePlacements));
        self::assertTrue(Role::Editor->can(Capability::ManageCreatives));
        self::assertTrue(Role::Editor->can(Capability::ViewMetrics));

        self::assertFalse(Role::Editor->can(Capability::ManageUsers));
        self::assertFalse(Role::Editor->can(Capability::ViewUsers));
        self::assertFalse(Role::Editor->can(Capability::ManageSettings));
        self::assertFalse(Role::Editor->can(Capability::ReviewCreatives));
        self::assertFalse(Role::Editor->can(Capability::ManageMarketplace));
        self::assertFalse(Role::Editor->can(Capability::ViewSensitiveMetrics));
    }

    public function testAnalystCanOnlyViewMetrics(): void
    {
        self::assertSame([Capability::ViewMetrics], Role::Analyst->capabilities());
        self::assertTrue(Role::Analyst->can(Capability::ViewMetrics));

        foreach (Capability::cases() as $capability) {
            if ($capability !== Capability::ViewMetrics) {
                self::assertFalse(Role::Analyst->can($capability), $capability->value);
            }
        }
    }

    /** @return iterable<string, array{Role}> */
    public static function boundedRoles(): iterable
    {
        yield 'org_admin' => [Role::OrgAdmin];
        yield 'editor' => [Role::Editor];
        yield 'analyst' => [Role::Analyst];
    }

    #[DataProvider('boundedRoles')]
    public function testCanIsConsistentWithCapabilitiesList(Role $role): void
    {
        foreach (Capability::cases() as $capability) {
            self::assertSame(
                in_array($capability, $role->capabilities(), true),
                $role->can($capability),
                $capability->value,
            );
        }
    }
}
