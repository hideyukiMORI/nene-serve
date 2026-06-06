<?php

declare(strict_types=1);

namespace NeneServe\Tests\Tenant\Resolution;

use NeneServe\Tenant\Resolution\CustomDomainResolutionStrategy;
use NeneServe\Tenant\Resolution\EnvResolutionStrategy;
use NeneServe\Tenant\Resolution\OrgResolutionMode;
use NeneServe\Tenant\Resolution\PathPrefixResolutionStrategy;
use NeneServe\Tenant\Resolution\SubdomainResolutionStrategy;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

/**
 * The pure URL → org-slug resolution strategies (ADR 0006). Each strategy is
 * deterministic and returns null when it cannot identify a tenant.
 */
final class ResolutionStrategyTest extends TestCase
{
    private Psr17Factory $psr17;

    protected function setUp(): void
    {
        $this->psr17 = new Psr17Factory();
    }

    public function testEnvStrategyReturnsConfiguredSlugOrNull(): void
    {
        $request = $this->psr17->createServerRequest('GET', 'http://localhost/admin/placements');

        self::assertSame('acme', (new EnvResolutionStrategy('acme'))->resolve($request));
        self::assertNull((new EnvResolutionStrategy(''))->resolve($request));
    }

    public function testSubdomainStrategyExtractsLeadingLabel(): void
    {
        $strategy = new SubdomainResolutionStrategy('serve.example.com');

        self::assertSame(
            'acme',
            $strategy->resolve($this->psr17->createServerRequest('GET', 'http://acme.serve.example.com/admin/x')),
        );
    }

    public function testSubdomainStrategyIgnoresPortAndCase(): void
    {
        $strategy = new SubdomainResolutionStrategy('Serve.Example.com');

        self::assertSame(
            'acme',
            $strategy->resolve($this->psr17->createServerRequest('GET', 'http://ACME.serve.example.com:8910/admin/x')),
        );
    }

    public function testSubdomainStrategyReturnsNullOnBareOrForeignDomain(): void
    {
        $strategy = new SubdomainResolutionStrategy('serve.example.com');

        self::assertNull($strategy->resolve($this->psr17->createServerRequest('GET', 'http://serve.example.com/admin/x')));
        self::assertNull($strategy->resolve($this->psr17->createServerRequest('GET', 'http://acme.evil.test/admin/x')));
    }

    public function testPathPrefixStrategyReturnsFirstSegment(): void
    {
        $strategy = new PathPrefixResolutionStrategy();

        self::assertSame('acme', $strategy->resolve($this->psr17->createServerRequest('GET', 'http://localhost/acme/admin/placements')));
    }

    public function testPathPrefixStrategyBypassesGlobalSurfaces(): void
    {
        $strategy = new PathPrefixResolutionStrategy();

        self::assertNull($strategy->resolve($this->psr17->createServerRequest('GET', 'http://localhost/health')));
        self::assertNull($strategy->resolve($this->psr17->createServerRequest('GET', 'http://localhost/public/placements/pk/serve')));
        self::assertNull($strategy->resolve($this->psr17->createServerRequest('GET', 'http://localhost/api/placements')));
    }

    public function testCustomDomainStrategyReturnsHost(): void
    {
        $strategy = new CustomDomainResolutionStrategy();

        self::assertSame(
            'ads.acme.com',
            $strategy->resolve($this->psr17->createServerRequest('GET', 'http://ads.acme.com:8910/admin/x')),
        );
    }

    public function testModeFromEnvFallsBackToLogin(): void
    {
        self::assertSame(OrgResolutionMode::Subdomain, OrgResolutionMode::fromEnv('subdomain'));
        self::assertSame(OrgResolutionMode::Path, OrgResolutionMode::fromEnv(' PATH '));
        self::assertSame(OrgResolutionMode::Login, OrgResolutionMode::fromEnv(null));
        self::assertSame(OrgResolutionMode::Login, OrgResolutionMode::fromEnv('nonsense'));
    }

    public function testModeUrlResolutionAndPathStripFlags(): void
    {
        self::assertFalse(OrgResolutionMode::Login->usesUrlResolution());
        self::assertTrue(OrgResolutionMode::Subdomain->usesUrlResolution());
        self::assertTrue(OrgResolutionMode::Path->stripsPathPrefix());
        self::assertFalse(OrgResolutionMode::Subdomain->stripsPathPrefix());
    }
}
