<?php

declare(strict_types=1);

namespace NeneServe\Tests\Service;

use NeneServe\Service\Auth\ScopeResolver;
use NeneServe\Service\Scope;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Service-route → required {@see Scope} table (api-security §5). Exact-match
 * routes must not leak to neighbours; unmapped routes resolve to null.
 */
final class ScopeResolverTest extends TestCase
{
    /** @return iterable<string, array{string, string, Scope|null}> */
    public static function routes(): iterable
    {
        yield 'placements' => ['/api/placements', 'GET', Scope::ReadPlacements];
        yield 'metrics' => ['/api/metrics', 'GET', Scope::ReadMetrics];
        yield 'metrics export' => ['/api/metrics/export', 'GET', Scope::ReadMetrics];
        yield 'delivery plan changes' => ['/api/delivery-plan-changes', 'POST', Scope::WriteDeliveryPlan];
        yield 'delivery plan sub-path' => ['/api/delivery-plan-changes/plan-1/apply', 'POST', Scope::WriteDeliveryPlan];

        yield 'placements with trailing segment is unmapped' => ['/api/placements/plc-1', 'GET', null];
        yield 'metrics neighbour is unmapped' => ['/api/metrics-summary', 'GET', null];
        yield 'unknown api route' => ['/api/unknown', 'GET', null];
        yield 'non-api route' => ['/admin/placements', 'GET', null];
    }

    #[DataProvider('routes')]
    public function testResolvesRequiredScope(string $path, string $method, ?Scope $expected): void
    {
        self::assertSame($expected, ScopeResolver::resolve($path, $method));
    }
}
