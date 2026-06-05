<?php

declare(strict_types=1);

namespace NeneServe\Tests\Tenant\Auth;

use NeneServe\Tenant\Auth\AdminAuthMiddleware;
use NeneServe\Tenant\Auth\AuthContextRequiredException;
use NeneServe\Tenant\Auth\AuthContextResolver;
use NeneServe\Tenant\Role;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Rebuilding the principal from verified token claims. Every required claim
 * (sub/org/role) must be a present, well-typed value or the whole context is
 * rejected — a partial principal must never leak through.
 */
final class AuthContextResolverTest extends TestCase
{
    private function request(mixed $claims): ServerRequestInterface
    {
        $request = (new Psr17Factory())->createServerRequest('GET', '/admin/me');

        return $claims === null ? $request : $request->withAttribute(AdminAuthMiddleware::CLAIMS_ATTRIBUTE, $claims);
    }

    public function testRebuildsContextFromValidClaims(): void
    {
        $context = AuthContextResolver::fromRequest($this->request([
            'sub' => 'u-1',
            'org' => 'org-acme',
            'role' => 'org_admin',
        ]));

        self::assertNotNull($context);
        self::assertSame('u-1', $context->userId);
        self::assertSame('org-acme', $context->organizationId);
        self::assertSame(Role::OrgAdmin, $context->role);
    }

    /** @return iterable<string, array{mixed}> */
    public static function rejectedClaims(): iterable
    {
        yield 'no claims attribute' => [null];
        yield 'claims not an array' => ['a string'];
        yield 'missing sub' => [['org' => 'org-1', 'role' => 'editor']];
        yield 'missing org' => [['sub' => 'u-1', 'role' => 'editor']];
        yield 'missing role' => [['sub' => 'u-1', 'org' => 'org-1']];
        yield 'unknown role' => [['sub' => 'u-1', 'org' => 'org-1', 'role' => 'wizard']];
        yield 'empty role' => [['sub' => 'u-1', 'org' => 'org-1', 'role' => '']];
        yield 'non-string sub' => [['sub' => 123, 'org' => 'org-1', 'role' => 'editor']];
        yield 'non-string org' => [['sub' => 'u-1', 'org' => ['x'], 'role' => 'editor']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('rejectedClaims')]
    public function testFromRequestReturnsNullForIncompleteClaims(mixed $claims): void
    {
        self::assertNull(AuthContextResolver::fromRequest($this->request($claims)));
    }

    public function testRequireReturnsContextWhenPresent(): void
    {
        $context = AuthContextResolver::require($this->request(['sub' => 'u-1', 'org' => 'o', 'role' => 'analyst']));

        self::assertSame('u-1', $context->userId);
    }

    public function testRequireThrowsWhenClaimsAreMissing(): void
    {
        $this->expectException(AuthContextRequiredException::class);
        AuthContextResolver::require($this->request(null));
    }

    public function testRequireThrowsWhenRoleIsInvalid(): void
    {
        $this->expectException(AuthContextRequiredException::class);
        AuthContextResolver::require($this->request(['sub' => 'u-1', 'org' => 'o', 'role' => 'nope']));
    }
}
