<?php

declare(strict_types=1);

namespace NeneServe\Support;

use NeneServe\Service\InMemoryServiceTokenRepository;
use NeneServe\Service\Scope;
use NeneServe\Service\ServiceToken;
use NeneServe\Serving\Creative;
use NeneServe\Serving\CreativeType;
use NeneServe\Serving\InMemoryCreativeRepository;
use NeneServe\Serving\InMemoryPlacementRepository;
use NeneServe\Serving\Placement;
use NeneServe\Serving\ReviewStatus;
use NeneServe\Tenant\InMemoryOrganizationRepository;
use NeneServe\Tenant\InMemoryUserRepository;
use NeneServe\Tenant\Organization;
use NeneServe\Tenant\Role;
use NeneServe\Tenant\User;

/**
 * Deterministic seed data for local `php -S` boot and tests, before the MySQL
 * stack and migrations are wired into the kernel. Two tenants exercise tenant
 * isolation; one cross-tenant superadmin exercises the bypass.
 *
 * NOT for production — production injects the PDO repositories.
 */
final class DevFixtures
{
    public const PASSWORD = 'secret123';
    public const SERVICE_TOKEN = 'svc-acme-readonly-secret';

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
            new User('user-acme-editor', 'org-acme', 'editor@acme.test', Role::Editor, $hash),
            new User('user-acme-analyst', 'org-acme', 'analyst@acme.test', Role::Analyst, $hash),
            new User('user-globex-admin', 'org-globex', 'admin@globex.test', Role::OrgAdmin, $hash),
            new User('user-root', 'org-acme', 'root@serve.test', Role::SuperAdmin, $hash),
        ]);
    }

    public static function placements(): InMemoryPlacementRepository
    {
        return new InMemoryPlacementRepository([
            // Active placement with an approved default creative → serves.
            new Placement('plc-acme-home', 'org-acme', 'pk_acme_home', ['https://acme.test'], 'active', 'cr-acme-banner'),
            // Active placement whose default creative is only draft → empty serve (204).
            new Placement('plc-acme-side', 'org-acme', 'pk_acme_side', [], 'active', 'cr-acme-draft'),
        ]);
    }

    public static function creatives(): InMemoryCreativeRepository
    {
        return new InMemoryCreativeRepository([
            new Creative(
                'cr-acme-banner',
                'org-acme',
                CreativeType::Image,
                ReviewStatus::Approved,
                'https://acme.test/landing',
                'https://cdn.acme.test/banner.png',
                300,
                250,
            ),
            new Creative(
                'cr-acme-draft',
                'org-acme',
                CreativeType::Image,
                ReviewStatus::Draft,
                'https://acme.test/landing',
                'https://cdn.acme.test/draft.png',
            ),
        ]);
    }

    public static function serviceTokens(): InMemoryServiceTokenRepository
    {
        return new InMemoryServiceTokenRepository([
            new ServiceToken(
                'svctok-acme',
                'org-acme',
                hash('sha256', self::SERVICE_TOKEN),
                [Scope::ReadPlacements],
            ),
        ]);
    }
}
