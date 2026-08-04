<?php

declare(strict_types=1);

namespace NeneServe\Tests\Runtime;

use Nene2\Config\AppConfig;
use NeneServe\Http\RateLimit\RateLimitStoreMode;
use NeneServe\Http\RuntimeContainerFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Phase 1 of the NENE2 migration: the application boots on the framework
 * runtime (container builds, config loads, the PSR-15 app handles requests).
 * Domain routes arrive in Phase 2; here an unknown route must produce the
 * framework's RFC 9457 problem-details 404.
 *
 * The throttle middleware runs on every route, so since #199 handling a request
 * reaches the shared counter table. This suite has no database, so it takes the
 * documented non-production opt-out — which is what that opt-out is for. The
 * binding itself is asserted in {@see RateLimitStorageWiringTest}.
 */
final class RuntimeBootTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER[RateLimitStoreMode::ENV_KEY] = 'memory';
    }

    protected function tearDown(): void
    {
        unset($_SERVER[RateLimitStoreMode::ENV_KEY]);
    }

    public function testContainerLoadsApplicationConfig(): void
    {
        $container = (new RuntimeContainerFactory(dirname(__DIR__, 2)))->create();

        $config = $container->get(AppConfig::class);

        self::assertInstanceOf(AppConfig::class, $config);
    }

    public function testRuntimeHandlesRequestAndReturnsProblemDetailsForUnknownRoute(): void
    {
        $container = (new RuntimeContainerFactory(dirname(__DIR__, 2)))->create();

        $application = $container->get(RequestHandlerInterface::class);
        self::assertInstanceOf(RequestHandlerInterface::class, $application);

        $request = (new Psr17Factory())->createServerRequest('GET', '/no-such-route');
        $response = $application->handle($request);

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('application/problem+json', $response->getHeaderLine('Content-Type'));
    }

    public function testHealthRouteIsServedByTheRuntime(): void
    {
        $container = (new RuntimeContainerFactory(dirname(__DIR__, 2)))->create();

        $application = $container->get(RequestHandlerInterface::class);
        self::assertInstanceOf(RequestHandlerInterface::class, $application);

        $request = (new Psr17Factory())->createServerRequest('GET', '/health');
        $response = $application->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        /** @var array<string, mixed> $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertSame('ok', $body['status'] ?? null);
    }
}
