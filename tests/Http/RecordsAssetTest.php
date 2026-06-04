<?php

declare(strict_types=1);

namespace NeneServe\Tests\Http;

use NeneServe\Http\Kernel;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Support\DevFixtures;
use NeneServe\Upstream\Records\FakeRecordsClient;
use NeneServe\Upstream\Records\RecordsAsset;
use PHPUnit\Framework\TestCase;

/**
 * Records read integration (ADR 0002, read-only): an operator fetches asset
 * metadata to assemble a creative; unknown refs 404; capability-gated.
 */
final class RecordsAssetTest extends TestCase
{
    public function testFetchKnownAsset(): void
    {
        $kernel = new Kernel(records: new FakeRecordsClient([
            new RecordsAsset('prod-42', 'https://cdn.acme.test/p42.png', 300, 250),
        ]));
        $editor = $this->login($kernel, 'editor@acme.test');

        $response = $this->get($kernel, '/admin/records-assets/prod-42', $editor);
        self::assertSame(200, $response->status);
        $body = $this->decode($response->body);
        self::assertSame('https://cdn.acme.test/p42.png', $body['image_url']);
        self::assertSame(300, $body['width']);
    }

    public function testUnknownAssetIs404(): void
    {
        $kernel = new Kernel(records: new FakeRecordsClient());
        $editor = $this->login($kernel, 'editor@acme.test');

        $response = $this->get($kernel, '/admin/records-assets/nope', $editor);
        self::assertSame(404, $response->status);
        self::assertStringEndsWith('/problems/records-asset-not-found', $this->decode($response->body)['type']);
    }

    public function testRequiresManageCreatives(): void
    {
        $kernel = new Kernel(records: new FakeRecordsClient([new RecordsAsset('x', 'https://cdn.acme.test/x.png')]));
        $analyst = $this->login($kernel, 'analyst@acme.test'); // no manage_creatives

        $response = $this->get($kernel, '/admin/records-assets/x', $analyst);
        self::assertSame(403, $response->status);
        self::assertStringEndsWith('/problems/insufficient-capability', $this->decode($response->body)['type']);
    }

    private function get(Kernel $kernel, string $path, string $token): Response
    {
        return $kernel->handle(new Request('GET', $path, ['authorization' => 'Bearer ' . $token]));
    }

    private function login(Kernel $kernel, string $email): string
    {
        $body = (string) json_encode(['organization' => 'acme', 'email' => $email, 'password' => DevFixtures::PASSWORD]);

        return (string) $this->decode($kernel->handle(new Request('POST', '/admin/login', [], [], $body))->body)['token'];
    }

    /** @return array<string, mixed> */
    private function decode(string $json): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($json, true);

        return $decoded;
    }
}
