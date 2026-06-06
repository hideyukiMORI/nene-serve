<?php

declare(strict_types=1);

namespace NeneServe\Tests\Auth;

use Nene2\Http\JsonResponseFactory;
use NeneServe\Auth\TenantContextHandler;
use NeneServe\Tenant\Organization;
use NeneServe\Tenant\OrganizationRepositoryInterface;
use NeneServe\Tenant\Resolution\OrgResolutionMode;
use NeneServe\Tenant\Resolution\OrgResolverMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

final class TenantContextHandlerTest extends TestCase
{
    public function testLoginModeReportsModeWithoutOrganization(): void
    {
        $body = $this->handle(OrgResolutionMode::Login, null);

        self::assertSame('login', $body['mode']);
        self::assertNull($body['organization']);
    }

    public function testUrlModeReportsResolvedOrganization(): void
    {
        $body = $this->handle(OrgResolutionMode::Subdomain, 'org-acme');

        self::assertSame('subdomain', $body['mode']);
        self::assertSame(['slug' => 'acme', 'name' => 'Acme'], $body['organization']);
    }

    public function testUrlModeWithUnresolvedTenantReportsNullOrganization(): void
    {
        // Subdomain mode on the bare domain: the middleware attached nothing.
        $body = $this->handle(OrgResolutionMode::Subdomain, null);

        self::assertSame('subdomain', $body['mode']);
        self::assertNull($body['organization']);
    }

    /**
     * @return array{mode: string, organization: array{slug: string, name: string}|null}
     */
    private function handle(OrgResolutionMode $mode, ?string $resolvedOrgId): array
    {
        $psr17 = new Psr17Factory();
        $repository = new class () implements OrganizationRepositoryInterface {
            public function findById(string $id): ?Organization
            {
                return $id === 'org-acme' ? new Organization('org-acme', 'acme', 'Acme', 'en', 'active') : null;
            }

            public function findBySlug(string $slug): ?Organization
            {
                return null;
            }

            public function findByCustomDomain(string $domain): ?Organization
            {
                return null;
            }
        };

        $handler = new TenantContextHandler($mode, $repository, new JsonResponseFactory($psr17, $psr17));

        $request = $psr17->createServerRequest('GET', '/admin/tenant-context');

        if ($resolvedOrgId !== null) {
            $request = $request->withAttribute(OrgResolverMiddleware::RESOLVED_ORG_ID_ATTRIBUTE, $resolvedOrgId);
        }

        /** @var array{mode: string, organization: array{slug: string, name: string}|null} $decoded */
        $decoded = json_decode((string) $handler->handle($request)->getBody(), true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }
}
