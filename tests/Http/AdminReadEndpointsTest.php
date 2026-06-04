<?php

declare(strict_types=1);

namespace NeneServe\Tests\Http;

use NeneServe\Http\Kernel;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Support\DevFixtures;
use PHPUnit\Framework\TestCase;

/**
 * Admin read/list endpoints used by the operator console (#71): tenant-scoped,
 * capability-gated, RFC 9457 Problem Details on 404.
 */
final class AdminReadEndpointsTest extends TestCase
{
    private Kernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = new Kernel();
    }

    public function testListPlacementsReturnsTenantPlacements(): void
    {
        $token = $this->login('admin@acme.test');
        $response = $this->get('/admin/placements', $token);

        self::assertSame(200, $response->status);
        $keys = array_column($this->decode($response->body)['placements'], 'public_placement_key');
        self::assertContains('pk_acme_home', $keys);
    }

    public function testGetPlacementByIdReturnsTheAdminProjection(): void
    {
        $token = $this->login('admin@acme.test');
        $body = $this->decode($this->get('/admin/placements/plc-acme-home', $token)->body);

        self::assertSame('pk_acme_home', $body['public_placement_key']);
        self::assertArrayHasKey('allowed_origins', $body);
        self::assertArrayHasKey('measurement_enabled', $body);
    }

    public function testUnknownPlacementIs404ProblemDetails(): void
    {
        $token = $this->login('admin@acme.test');
        $response = $this->get('/admin/placements/plc-nope', $token);

        self::assertSame(404, $response->status);
        self::assertStringEndsWith('/problems/placement-not-found', $this->decode($response->body)['type']);
    }

    public function testGetCreativeByIdReturnsCreative(): void
    {
        $token = $this->login('admin@acme.test');
        $body = $this->decode($this->get('/admin/creatives/cr-acme-banner', $token)->body);

        self::assertSame('cr-acme-banner', $body['id']);
    }

    public function testUnknownCreativeIs404(): void
    {
        $token = $this->login('admin@acme.test');
        $response = $this->get('/admin/creatives/cr-nope', $token);

        self::assertSame(404, $response->status);
        self::assertStringEndsWith('/problems/creative-not-found', $this->decode($response->body)['type']);
    }

    public function testListCampaignsAndPricingRulesAreEnvelopedArrays(): void
    {
        $token = $this->login('admin@acme.test');

        self::assertArrayHasKey('campaigns', $this->decode($this->get('/admin/campaigns', $token)->body));
        self::assertArrayHasKey('pricing_rules', $this->decode($this->get('/admin/pricing-rules', $token)->body));
    }

    public function testReadEndpointsRequireAuthentication(): void
    {
        $response = $this->kernel->handle(new Request('GET', '/admin/placements'));
        self::assertSame(401, $response->status);
    }

    private function get(string $path, string $token): Response
    {
        return $this->kernel->handle(new Request('GET', $path, ['authorization' => 'Bearer ' . $token]));
    }

    private function login(string $email): string
    {
        $response = $this->kernel->handle(new Request('POST', '/admin/login', [], [], (string) json_encode([
            'organization' => 'acme',
            'email' => $email,
            'password' => DevFixtures::PASSWORD,
        ])));
        self::assertSame(200, $response->status, 'login failed for ' . $email);

        return (string) $this->decode($response->body)['token'];
    }

    /** @return array<string, mixed> */
    private function decode(string $body): array
    {
        /** @var array<string, mixed> $data */
        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        return $data;
    }
}
