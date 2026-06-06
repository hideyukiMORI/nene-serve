<?php

declare(strict_types=1);

namespace NeneServe\Tests\Tenant\Resolution;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Tenant\Organization;
use NeneServe\Tenant\OrganizationRepositoryInterface;
use NeneServe\Tenant\Resolution\CustomDomainResolutionStrategy;
use NeneServe\Tenant\Resolution\EnvResolutionStrategy;
use NeneServe\Tenant\Resolution\OrgResolutionMode;
use NeneServe\Tenant\Resolution\OrgResolutionStrategyInterface;
use NeneServe\Tenant\Resolution\OrgResolverMiddleware;
use NeneServe\Tenant\Resolution\PathPrefixResolutionStrategy;
use NeneServe\Tenant\Resolution\SubdomainResolutionStrategy;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * {@see OrgResolverMiddleware} fails closed on the admin surface, leaves the
 * global/public/service surfaces untouched, strips the org prefix in path mode,
 * and exposes the resolved tenant for the admin auth middleware to reconcile.
 */
final class OrgResolverMiddlewareTest extends TestCase
{
    private Psr17Factory $psr17;

    /** @var RequestScopedHolder<string> */
    private RequestScopedHolder $holder;

    protected function setUp(): void
    {
        $this->psr17 = new Psr17Factory();
        $this->holder = new RequestScopedHolder();
    }

    public function testSubdomainResolvesActiveTenant(): void
    {
        [$response, $seen] = $this->dispatch(
            OrgResolutionMode::Subdomain,
            new SubdomainResolutionStrategy('serve.example.com'),
            'http://acme.serve.example.com/admin/placements',
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($this->holder->isSet());
        self::assertSame('org-acme', $this->holder->get());
        self::assertSame('/admin/placements', $seen->path);
        self::assertSame('org-acme', $seen->resolvedOrgId);
        self::assertSame('acme', $seen->resolvedOrgSlug);
    }

    public function testUnknownTenantIsRejected(): void
    {
        [$response] = $this->dispatch(
            OrgResolutionMode::Subdomain,
            new SubdomainResolutionStrategy('serve.example.com'),
            'http://ghost.serve.example.com/admin/placements',
        );

        self::assertSame(404, $response->getStatusCode());
        self::assertFalse($this->holder->isSet());
    }

    public function testUnresolvableTenantOnAdminSurfaceIsRejected(): void
    {
        [$response] = $this->dispatch(
            OrgResolutionMode::Subdomain,
            new SubdomainResolutionStrategy('serve.example.com'),
            'http://serve.example.com/admin/placements',
        );

        self::assertSame(404, $response->getStatusCode());
    }

    public function testInactiveTenantIsForbidden(): void
    {
        [$response] = $this->dispatch(
            OrgResolutionMode::Single,
            new EnvResolutionStrategy('suspended'),
            'http://localhost/admin/placements',
        );

        self::assertSame(403, $response->getStatusCode());
        self::assertFalse($this->holder->isSet());
    }

    public function testLoginRouteBypassesResolution(): void
    {
        [$response, $seen] = $this->dispatch(
            OrgResolutionMode::Subdomain,
            new SubdomainResolutionStrategy('serve.example.com'),
            'http://serve.example.com/admin/login',
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertFalse($this->holder->isSet());
        self::assertNull($seen->resolvedOrgId);
    }

    public function testPublicSurfaceIsNeverTenantByUrl(): void
    {
        [$response, $seen] = $this->dispatch(
            OrgResolutionMode::Subdomain,
            new SubdomainResolutionStrategy('serve.example.com'),
            'http://serve.example.com/public/placements/pk/serve',
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertFalse($this->holder->isSet());
        self::assertSame('/public/placements/pk/serve', $seen->path);
    }

    public function testPathModeStripsPrefixBeforeRouting(): void
    {
        [$response, $seen] = $this->dispatch(
            OrgResolutionMode::Path,
            new PathPrefixResolutionStrategy(),
            'http://localhost/acme/admin/placements',
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('org-acme', $this->holder->get());
        self::assertSame('/admin/placements', $seen->path);
    }

    public function testPathModeStripsPrefixForOpenLoginRoute(): void
    {
        [$response, $seen] = $this->dispatch(
            OrgResolutionMode::Path,
            new PathPrefixResolutionStrategy(),
            'http://localhost/acme/admin/login',
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertFalse($this->holder->isSet());
        self::assertSame('/admin/login', $seen->path);
    }

    public function testCustomDomainResolvesViaRepository(): void
    {
        [$response] = $this->dispatch(
            OrgResolutionMode::CustomDomain,
            new CustomDomainResolutionStrategy(),
            'http://ads.acme.com/admin/placements',
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('org-acme', $this->holder->get());
    }

    /**
     * @return array{0: ResponseInterface, 1: SeenRequest}
     */
    private function dispatch(
        OrgResolutionMode $mode,
        OrgResolutionStrategyInterface $strategy,
        string $uri,
    ): array {
        $middleware = new OrgResolverMiddleware(
            $this->holder,
            $this->fakeRepository(),
            new ProblemDetailsResponseFactory($this->psr17, $this->psr17),
            $strategy,
            $mode,
        );

        $seen = new SeenRequest();
        $next = new class ($this->psr17, $seen) implements RequestHandlerInterface {
            public function __construct(
                private readonly Psr17Factory $psr17,
                private readonly SeenRequest $seen,
            ) {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->seen->path = $request->getUri()->getPath();
                $resolvedId = $request->getAttribute(OrgResolverMiddleware::RESOLVED_ORG_ID_ATTRIBUTE);
                $resolvedSlug = $request->getAttribute(OrgResolverMiddleware::RESOLVED_ORG_SLUG_ATTRIBUTE);
                $this->seen->resolvedOrgId = is_string($resolvedId) ? $resolvedId : null;
                $this->seen->resolvedOrgSlug = is_string($resolvedSlug) ? $resolvedSlug : null;

                return $this->psr17->createResponse(200);
            }
        };

        $response = $middleware->process($this->psr17->createServerRequest('GET', $uri), $next);

        return [$response, $seen];
    }

    private function fakeRepository(): OrganizationRepositoryInterface
    {
        return new class () implements OrganizationRepositoryInterface {
            public function findById(string $id): ?Organization
            {
                return null;
            }

            public function findBySlug(string $slug): ?Organization
            {
                return match ($slug) {
                    'acme' => new Organization('org-acme', 'acme', 'Acme', 'en', 'active'),
                    'suspended' => new Organization('org-susp', 'suspended', 'Suspended', 'en', 'suspended'),
                    default => null,
                };
            }

            public function findByCustomDomain(string $domain): ?Organization
            {
                return $domain === 'ads.acme.com'
                    ? new Organization('org-acme', 'acme', 'Acme', 'en', 'active')
                    : null;
            }
        };
    }
}

/** Captures what the downstream handler observed. */
final class SeenRequest
{
    public ?string $path = null;

    public ?string $resolvedOrgId = null;

    public ?string $resolvedOrgSlug = null;
}
