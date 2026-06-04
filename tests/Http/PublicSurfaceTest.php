<?php

declare(strict_types=1);

namespace NeneServe\Tests\Http;

use NeneServe\Http\Kernel;
use NeneServe\Http\RateLimit\InMemoryRateLimiter;
use NeneServe\Http\Request;
use NeneServe\Serving\Token\InMemoryTokenStore;
use PHPUnit\Framework\TestCase;

/**
 * Public serve surface (api-security §2, ADR 0019): serve → impression → click,
 * origin gating, opaque-token-only payloads, idempotent beacon, single-use and
 * expiring click tokens, and no open redirect.
 */
final class PublicSurfaceTest extends TestCase
{
    public function testServeReturnsPayloadWithOpaqueTokensOnly(): void
    {
        $response = (new Kernel())->handle($this->serveRequest('pk_acme_home'));

        self::assertSame(200, $response->status);
        $body = $this->decode($response->body);

        self::assertSame('image', $body['creative']['type']);
        self::assertSame('https://cdn.acme.test/banner.png', $body['creative']['asset_url']);
        self::assertArrayHasKey('impression_token', $body);
        self::assertStringStartsWith('/public/clicks/', $body['click_url']);

        // Least exposure: no internal ids / org / destination leaked.
        $flat = json_encode($body);
        self::assertStringNotContainsString('org-acme', (string) $flat);
        self::assertStringNotContainsString('plc-acme', (string) $flat);
        self::assertStringNotContainsString('acme.test/landing', (string) $flat);
    }

    public function testUnknownPlacementIs404(): void
    {
        $response = (new Kernel())->handle($this->serveRequest('pk_nope'));

        self::assertSame(404, $response->status);
        self::assertStringEndsWith('/problems/placement-not-found', $this->decode($response->body)['type']);
    }

    public function testDisallowedOriginIs403(): void
    {
        $response = (new Kernel())->handle(new Request(
            'GET',
            '/public/placements/pk_acme_home/serve',
            ['origin' => 'https://evil.test'],
        ));

        self::assertSame(403, $response->status);
        self::assertStringEndsWith('/problems/origin-not-allowed', $this->decode($response->body)['type']);
    }

    public function testAllowedOriginIsReflectedInCors(): void
    {
        $response = (new Kernel())->handle(new Request(
            'GET',
            '/public/placements/pk_acme_home/serve',
            ['origin' => 'https://acme.test'],
        ));

        self::assertSame(200, $response->status);
        self::assertArrayHasKey('Access-Control-Allow-Origin', $response->headers);
        self::assertSame('https://acme.test', $response->headers['Access-Control-Allow-Origin']);
    }

    public function testNonApprovedCreativeIsEmptyServe204(): void
    {
        // pk_acme_side's default creative is only draft → nothing serves.
        $response = (new Kernel())->handle($this->serveRequest('pk_acme_side'));

        self::assertSame(204, $response->status);
        self::assertSame('', $response->body);
    }

    public function testClickRedirectsToRegisteredDestinationOnce(): void
    {
        $kernel = new Kernel(tokens: new InMemoryTokenStore());
        $token = $this->clickTokenFromServe($kernel);

        $first = $kernel->handle($this->clickRequest($token));
        self::assertSame(302, $first->status);
        self::assertSame('https://acme.test/landing', $first->headers['Location'] ?? null);

        // Single use: a replay is rejected, never a fallback redirect.
        $second = $kernel->handle($this->clickRequest($token));
        self::assertSame(404, $second->status);
        self::assertStringEndsWith('/problems/click-token-invalid', $this->decode($second->body)['type']);
    }

    public function testExpiredClickTokenIsRejected(): void
    {
        $now = 1_000_000;
        $store = new InMemoryTokenStore(static function () use (&$now): int {
            return $now;
        });
        $kernel = new Kernel(tokens: $store);

        $token = $this->clickTokenFromServe($kernel);
        $now += 901; // past the 900s default TTL

        $response = $kernel->handle($this->clickRequest($token));
        self::assertSame(404, $response->status);
    }

    public function testUnknownClickTokenIsRejected(): void
    {
        $response = (new Kernel())->handle($this->clickRequest('deadbeef'));

        self::assertSame(404, $response->status);
    }

    public function testImpressionBeaconRequiresTokenButIsIdempotent(): void
    {
        $kernel = new Kernel();

        $missing = $kernel->handle(new Request('POST', '/public/events/impression', [], [], '{}'));
        self::assertSame(422, $missing->status);

        // A well-formed beacon acks 204, and replays stay 204 (no inflation).
        $body = (string) json_encode(['impression_token' => 'whatever']);
        self::assertSame(204, $kernel->handle(new Request('POST', '/public/events/impression', [], [], $body))->status);
        self::assertSame(204, $kernel->handle(new Request('POST', '/public/events/impression', [], [], $body))->status);
    }

    public function testRateLimitReturns429(): void
    {
        $kernel = new Kernel(rateLimiter: new InMemoryRateLimiter(limit: 1));

        self::assertSame(200, $kernel->handle($this->serveRequest('pk_acme_home'))->status);
        self::assertSame(429, $kernel->handle($this->serveRequest('pk_acme_home'))->status);
    }

    private function clickTokenFromServe(Kernel $kernel): string
    {
        $body = $this->decode($kernel->handle($this->serveRequest('pk_acme_home'))->body);

        return substr((string) $body['click_url'], strlen('/public/clicks/'));
    }

    private function serveRequest(string $key): Request
    {
        return new Request('GET', '/public/placements/' . $key . '/serve');
    }

    private function clickRequest(string $token): Request
    {
        return new Request('GET', '/public/clicks/' . $token);
    }

    /** @return array<string, mixed> */
    private function decode(string $json): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($json, true);

        return $decoded;
    }
}
