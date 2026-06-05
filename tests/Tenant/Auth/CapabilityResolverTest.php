<?php

declare(strict_types=1);

namespace NeneServe\Tests\Tenant\Auth;

use NeneServe\Tenant\Auth\CapabilityResolver;
use NeneServe\Tenant\Capability;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Route → required {@see Capability} table. The ordering matters (review-queue
 * before creatives; four-eyes endings inside /admin/creatives) and the users
 * resource splits on HTTP method, so each is probed explicitly.
 */
final class CapabilityResolverTest extends TestCase
{
    /** @return iterable<string, array{string, string, Capability|null}> */
    public static function routes(): iterable
    {
        // Authenticated-only (no capability)
        yield 'me needs only a session' => ['/admin/me', 'GET', null];
        yield 'unknown route' => ['/admin/unknown', 'GET', null];
        yield 'root' => ['/', 'GET', null];

        // Users split on method
        yield 'list users → view' => ['/admin/users', 'GET', Capability::ViewUsers];
        yield 'head users → view' => ['/admin/users', 'HEAD', Capability::ViewUsers];
        yield 'create user → manage' => ['/admin/users', 'POST', Capability::ManageUsers];
        yield 'delete user → manage' => ['/admin/users/u-1', 'DELETE', Capability::ManageUsers];
        yield 'method is case-insensitive' => ['/admin/users', 'get', Capability::ViewUsers];

        // Metrics
        yield 'metrics' => ['/admin/metrics', 'GET', Capability::ViewMetrics];
        yield 'metrics export' => ['/admin/metrics/export', 'GET', Capability::ViewMetrics];

        // Settings-family all map to ManageSettings
        yield 'settings' => ['/admin/settings/smtp', 'PUT', Capability::ManageSettings];
        yield 'dsr' => ['/admin/data-subject-requests', 'POST', Capability::ManageSettings];
        yield 'legal holds' => ['/admin/legal-holds', 'POST', Capability::ManageSettings];

        // Placements
        yield 'placements' => ['/admin/placements', 'POST', Capability::ManagePlacements];

        // Review queue must win over the creatives prefix
        yield 'review queue' => ['/admin/review-queue', 'GET', Capability::ReviewCreatives];

        // Creatives four-eyes: author vs reviewer actions
        yield 'create creative → manage' => ['/admin/creatives', 'POST', Capability::ManageCreatives];
        yield 'submit (author) → manage' => ['/admin/creatives/cr-1/submit', 'POST', Capability::ManageCreatives];
        yield 'revise (author) → manage' => ['/admin/creatives/cr-1/revise', 'POST', Capability::ManageCreatives];
        yield 'start-review (reviewer)' => ['/admin/creatives/cr-1/start-review', 'POST', Capability::ReviewCreatives];
        yield 'approve (reviewer)' => ['/admin/creatives/cr-1/approve', 'POST', Capability::ReviewCreatives];
        yield 'reject (reviewer)' => ['/admin/creatives/cr-1/reject', 'POST', Capability::ReviewCreatives];
        yield 'request-changes (reviewer)' => ['/admin/creatives/cr-1/request-changes', 'POST', Capability::ReviewCreatives];

        // Assets
        yield 'assets → manage creatives' => ['/admin/assets', 'POST', Capability::ManageCreatives];
        yield 'records-assets → manage creatives' => ['/admin/records-assets/ref', 'GET', Capability::ManageCreatives];

        // Marketplace family
        yield 'advertisers' => ['/admin/advertisers', 'POST', Capability::ManageMarketplace];
        yield 'pricing-rules' => ['/admin/pricing-rules', 'POST', Capability::ManageMarketplace];
        yield 'campaigns' => ['/admin/campaigns', 'POST', Capability::ManageMarketplace];
        yield 'billing-periods' => ['/admin/billing-periods/bp-1/handoff', 'POST', Capability::ManageMarketplace];
    }

    #[DataProvider('routes')]
    public function testResolvesRequiredCapability(string $path, string $method, ?Capability $expected): void
    {
        self::assertSame($expected, CapabilityResolver::resolve($path, $method));
    }
}
