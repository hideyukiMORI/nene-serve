<?php

declare(strict_types=1);

namespace NeneServe\Tests\Tenant\Auth;

use Nene2\Auth\TokenIssuerInterface;
use NeneServe\Http\RuntimeContainerFactory;
use NeneServe\Tenant\Auth\AdminAuthMiddleware;
use NeneServe\Tenant\Auth\CapabilityMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * The NENE2 admin auth + capability middleware on Bearer tokens minted by
 * {@see \Nene2\Auth\LocalBearerTokenVerifier} (replacing the hand-rolled JWT).
 */
final class AdminAuthTest extends TestCase
{
    private AdminAuthMiddleware $admin;

    private CapabilityMiddleware $capability;

    private TokenIssuerInterface $issuer;

    protected function setUp(): void
    {
        $container = (new RuntimeContainerFactory(dirname(__DIR__, 3)))->create();

        $admin = $container->get(AdminAuthMiddleware::class);
        $capability = $container->get(CapabilityMiddleware::class);
        $issuer = $container->get(TokenIssuerInterface::class);

        self::assertInstanceOf(AdminAuthMiddleware::class, $admin);
        self::assertInstanceOf(CapabilityMiddleware::class, $capability);
        self::assertInstanceOf(TokenIssuerInterface::class, $issuer);

        $this->admin = $admin;
        $this->capability = $capability;
        $this->issuer = $issuer;
    }

    public function testProtectedRouteWithoutTokenIsRejected(): void
    {
        $response = $this->dispatch('GET', '/admin/users', null);

        self::assertSame(401, $response->getStatusCode());
    }

    public function testOpenRouteWithoutTokenPassesThrough(): void
    {
        $response = $this->dispatch('POST', '/admin/login', null);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testAdminWithCapabilityIsAllowed(): void
    {
        $token = $this->issuer->issue(['sub' => 'u-1', 'org_id' => 'org-acme', 'role' => 'org_admin', 'exp' => time() + 3600]);

        $response = $this->dispatch('GET', '/admin/users', $token);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testAuthenticatedUserLackingCapabilityIsForbidden(): void
    {
        // analyst can ViewMetrics but not ViewUsers.
        $token = $this->issuer->issue(['sub' => 'u-2', 'org_id' => 'org-acme', 'role' => 'analyst', 'exp' => time() + 3600]);

        $response = $this->dispatch('GET', '/admin/users', $token);

        self::assertSame(403, $response->getStatusCode());
    }

    private function dispatch(string $method, string $path, ?string $token): ResponseInterface
    {
        $request = (new Psr17Factory())->createServerRequest($method, $path);

        if ($token !== null) {
            $request = $request->withHeader('Authorization', 'Bearer ' . $token);
        }

        $ok = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return (new Psr17Factory())->createResponse(200);
            }
        };

        $capabilityStage = new class ($this->capability, $ok) implements RequestHandlerInterface {
            public function __construct(
                private readonly CapabilityMiddleware $capability,
                private readonly RequestHandlerInterface $next,
            ) {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->capability->process($request, $this->next);
            }
        };

        return $this->admin->process($request, $capabilityStage);
    }
}
