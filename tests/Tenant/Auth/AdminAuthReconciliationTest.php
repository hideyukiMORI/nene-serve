<?php

declare(strict_types=1);

namespace NeneServe\Tests\Tenant\Auth;

use Nene2\Auth\LocalBearerTokenVerifier;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Tenant\Auth\AdminAuthMiddleware;
use NeneServe\Tenant\Resolution\OrgResolverMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * When a URL resolution mode placed the tenant on the request, the admin auth
 * middleware reconciles the JWT against it (ADR 0006 / ADR 0018): a token for
 * another organization is refused, except for a cross-tenant superadmin.
 */
final class AdminAuthReconciliationTest extends TestCase
{
    private Psr17Factory $psr17;

    private LocalBearerTokenVerifier $tokens;

    /** @var RequestScopedHolder<string> */
    private RequestScopedHolder $holder;

    protected function setUp(): void
    {
        $this->psr17 = new Psr17Factory();
        $this->tokens = new LocalBearerTokenVerifier('test-secret');
        $this->holder = new RequestScopedHolder();
    }

    public function testMatchingTokenIsAllowedAndTenantIsAuthoritative(): void
    {
        $response = $this->dispatch('org-acme', 'editor', 'org-acme');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('org-acme', $this->holder->get());
    }

    public function testTokenForAnotherTenantIsForbidden(): void
    {
        $response = $this->dispatch('org-acme', 'editor', 'org-evil');

        self::assertSame(403, $response->getStatusCode());
        self::assertFalse($this->holder->isSet());
    }

    public function testSuperadminMayActWithinResolvedTenant(): void
    {
        $response = $this->dispatch('org-acme', 'superadmin', 'org-evil');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('org-acme', $this->holder->get());
    }

    public function testLoginModeUsesTokenOrgWhenNoTenantResolved(): void
    {
        $response = $this->dispatch('org-acme', 'editor', null);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('org-acme', $this->holder->get());
    }

    /**
     * @param string|null $tokenOrg the org claim to mint into the token; null
     *        means login mode (no tenant is resolved from the URL).
     */
    private function dispatch(string $resolvedTenant, string $role, ?string $tokenOrg): ResponseInterface
    {
        $middleware = new AdminAuthMiddleware(
            new ProblemDetailsResponseFactory($this->psr17, $this->psr17),
            $this->tokens,
            $this->holder,
        );

        // Login mode (no resolved tenant) carries the org only in the token.
        $token = $this->tokens->issue([
            'sub' => 'user-1',
            'org' => $tokenOrg ?? $resolvedTenant,
            'role' => $role,
            'iat' => time(),
            'exp' => time() + 3600,
        ]);

        $request = $this->psr17->createServerRequest('GET', 'http://localhost/admin/placements')
            ->withHeader('Authorization', 'Bearer ' . $token);

        if ($tokenOrg !== null) {
            $request = $request->withAttribute(OrgResolverMiddleware::RESOLVED_ORG_ID_ATTRIBUTE, $resolvedTenant);
        }

        $next = new class ($this->psr17) implements RequestHandlerInterface {
            public function __construct(private readonly Psr17Factory $psr17)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->psr17->createResponse(200);
            }
        };

        return $middleware->process($request, $next);
    }
}
