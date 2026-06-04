<?php

declare(strict_types=1);

namespace NeneServe\Tests\Http;

use NeneServe\Http\Kernel;
use NeneServe\Http\Request;
use NeneServe\Service\InMemoryServiceTokenRepository;
use NeneServe\Service\ServiceToken;
use NeneServe\Support\DevFixtures;
use PHPUnit\Framework\TestCase;

/**
 * Service surface `/api/*` (api-security §5): scoped opaque tokens, fail closed,
 * tenant-scoped results, `insufficient-scope` → 403.
 */
final class ServiceApiTest extends TestCase
{
    public function testMissingTokenIsUnauthorized(): void
    {
        $response = (new Kernel())->handle(new Request('GET', '/api/placements'));

        self::assertSame(401, $response->status);
        self::assertStringEndsWith('/problems/unauthorized', $this->decode($response->body)['type']);
    }

    public function testValidScopedTokenListsOwnTenant(): void
    {
        $response = (new Kernel())->handle(new Request(
            'GET',
            '/api/placements',
            ['authorization' => 'Bearer ' . DevFixtures::SERVICE_TOKEN],
        ));

        self::assertSame(200, $response->status);
        $keys = array_column($this->decode($response->body)['placements'], 'public_placement_key');
        self::assertContains('pk_acme_home', $keys);
    }

    public function testTokenWithoutScopeIsForbidden(): void
    {
        // A token with no scopes cannot read placements.
        $repo = new InMemoryServiceTokenRepository([
            new ServiceToken('svctok-empty', 'org-acme', hash('sha256', 'empty-secret'), []),
        ]);
        $kernel = new Kernel(serviceTokens: $repo);

        $response = $kernel->handle(new Request(
            'GET',
            '/api/placements',
            ['authorization' => 'Bearer empty-secret'],
        ));

        self::assertSame(403, $response->status);
        self::assertStringEndsWith('/problems/insufficient-scope', $this->decode($response->body)['type']);
    }

    /** @return array<string, mixed> */
    private function decode(string $json): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($json, true);

        return $decoded;
    }
}
