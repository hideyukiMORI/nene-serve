<?php

declare(strict_types=1);

namespace NeneServe\Tests\Http;

use NeneServe\Http\Kernel;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Support\DevFixtures;
use PHPUnit\Framework\TestCase;

/**
 * Legal-hold admin surface (billing §7): place/release require `manage_settings`
 * and are audited; releases tombstone (never delete).
 */
final class LegalHoldTest extends TestCase
{
    public function testPlaceAndReleaseLegalHold(): void
    {
        $kernel = new Kernel();
        $admin = $this->login($kernel, 'admin@acme.test');

        $placed = $this->post($kernel, '/admin/legal-holds', $admin, ['reason' => 'tax audit']);
        self::assertSame(201, $placed->status);
        $body = $this->decode($placed->body);
        self::assertTrue($body['active']);

        $released = $this->post($kernel, '/admin/legal-holds/' . $body['id'] . '/release', $admin, []);
        self::assertSame(200, $released->status);
        self::assertFalse($this->decode($released->body)['active']);
        self::assertNotNull($this->decode($released->body)['released_at']);
    }

    public function testLegalHoldRequiresManageSettings(): void
    {
        $kernel = new Kernel();
        $analyst = $this->login($kernel, 'analyst@acme.test');

        $response = $this->post($kernel, '/admin/legal-holds', $analyst, ['reason' => 'x']);
        self::assertSame(403, $response->status);
        self::assertStringEndsWith('/problems/insufficient-capability', $this->decode($response->body)['type']);
    }

    private function login(Kernel $kernel, string $email): string
    {
        $body = (string) json_encode(['organization' => 'acme', 'email' => $email, 'password' => DevFixtures::PASSWORD]);

        return (string) $this->decode($kernel->handle(new Request('POST', '/admin/login', [], [], $body))->body)['token'];
    }

    /** @param array<string, mixed> $body */
    private function post(Kernel $kernel, string $path, string $token, array $body): Response
    {
        return $kernel->handle(new Request('POST', $path, ['authorization' => 'Bearer ' . $token], [], (string) json_encode($body)));
    }

    /** @return array<string, mixed> */
    private function decode(string $json): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($json, true);

        return $decoded;
    }
}
