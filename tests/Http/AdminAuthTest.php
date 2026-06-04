<?php

declare(strict_types=1);

namespace NeneServe\Tests\Http;

use NeneServe\Http\Kernel;
use NeneServe\Http\Request;
use NeneServe\Support\DevFixtures;
use PHPUnit\Framework\TestCase;

/**
 * End-to-end admin auth over the kernel using {@see DevFixtures}: verifies the
 * three #11 acceptance points — JWT required, capability gating, and absolute
 * tenant isolation (ADR 0006, api-security §0.3–0.4), all fail-closed.
 */
final class AdminAuthTest extends TestCase
{
    private Kernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = new Kernel();
    }

    public function testLoginIssuesTokenAndMeReturnsPrincipal(): void
    {
        $token = $this->login('acme', 'admin@acme.test');

        $response = $this->kernel->handle($this->get('/admin/me', $token));
        self::assertSame(200, $response->status);

        $body = $this->decode($response->body);
        self::assertSame('admin@acme.test', $body['user']['email']);
        self::assertSame('org-acme', $body['user']['organization_id']);
        self::assertContains('view_users', $body['capabilities']);
    }

    public function testMeWithoutTokenIsUnauthorized(): void
    {
        $response = $this->kernel->handle($this->get('/admin/me', null));

        self::assertSame(401, $response->status);
        self::assertStringEndsWith('/problems/unauthorized', $this->decode($response->body)['type']);
    }

    public function testTamperedTokenIsUnauthorized(): void
    {
        $token = $this->login('acme', 'admin@acme.test');

        $response = $this->kernel->handle($this->get('/admin/me', $token . 'tampered'));
        self::assertSame(401, $response->status);
    }

    public function testWrongPasswordIsUnauthorized(): void
    {
        $response = $this->kernel->handle($this->post('/admin/login', [
            'organization' => 'acme',
            'email' => 'admin@acme.test',
            'password' => 'wrong',
        ]));

        self::assertSame(401, $response->status);
    }

    public function testLoginIsTenantScoped(): void
    {
        // A real acme user cannot authenticate against the globex tenant.
        $response = $this->kernel->handle($this->post('/admin/login', [
            'organization' => 'globex',
            'email' => 'admin@acme.test',
            'password' => DevFixtures::PASSWORD,
        ]));

        self::assertSame(401, $response->status);
    }

    public function testAnalystLacksViewUsersCapability(): void
    {
        $token = $this->login('acme', 'analyst@acme.test');

        $response = $this->kernel->handle($this->get('/admin/users', $token));

        self::assertSame(403, $response->status);
        self::assertStringEndsWith('/problems/insufficient-capability', $this->decode($response->body)['type']);
    }

    public function testListUsersIsTenantScoped(): void
    {
        $token = $this->login('acme', 'admin@acme.test');

        $body = $this->decode($this->kernel->handle($this->get('/admin/users', $token))->body);
        $emails = array_column($body['users'], 'email');

        self::assertContains('admin@acme.test', $emails);
        self::assertContains('analyst@acme.test', $emails);
        self::assertNotContains('admin@globex.test', $emails, 'tenant isolation breached');
    }

    public function testGlobexAdminSeesOnlyGlobex(): void
    {
        $token = $this->login('globex', 'admin@globex.test');

        $emails = array_column($this->decode($this->kernel->handle($this->get('/admin/users', $token))->body)['users'], 'email');

        self::assertSame(['admin@globex.test'], $emails);
    }

    public function testSuperadminSeesAllTenants(): void
    {
        $token = $this->login('acme', 'root@serve.test');

        $emails = array_column($this->decode($this->kernel->handle($this->get('/admin/users', $token))->body)['users'], 'email');

        self::assertContains('admin@acme.test', $emails);
        self::assertContains('admin@globex.test', $emails);
    }

    private function login(string $organization, string $email): string
    {
        $response = $this->kernel->handle($this->post('/admin/login', [
            'organization' => $organization,
            'email' => $email,
            'password' => DevFixtures::PASSWORD,
        ]));
        self::assertSame(200, $response->status, 'login failed');

        return (string) $this->decode($response->body)['token'];
    }

    /** @param array<string, mixed> $payload */
    private function post(string $path, array $payload): Request
    {
        return new Request('POST', $path, [], [], (string) json_encode($payload));
    }

    private function get(string $path, ?string $token): Request
    {
        $headers = $token === null ? [] : ['authorization' => 'Bearer ' . $token];

        return new Request('GET', $path, $headers);
    }

    /** @return array<string, mixed> */
    private function decode(string $json): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($json, true);

        return $decoded;
    }
}
