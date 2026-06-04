<?php

declare(strict_types=1);

namespace NeneServe\Tests\Http;

use NeneServe\Assets\InMemoryAssetRepository;
use NeneServe\Http\Kernel;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Storage\InMemoryStorage;
use NeneServe\Support\DevFixtures;
use PHPUnit\Framework\TestCase;

/**
 * Asset upload + public serving: allowlisted media is stored and streamed back
 * by opaque id with a fixed Content-Type (never executed); unsupported types and
 * oversized uploads are rejected; serving is capability-free but rate-limited.
 */
final class AssetUploadTest extends TestCase
{
    private Kernel $kernel;

    protected function setUp(): void
    {
        // Shared in-memory asset repo + storage so upload and serve see the same state.
        $this->kernel = new Kernel(
            assets: new InMemoryAssetRepository(),
            storage: new InMemoryStorage(),
        );
    }

    public function testUploadThenServeRoundTrip(): void
    {
        $token = $this->login('editor@acme.test');
        $pngBytes = "\x89PNG\r\n\x1a\nfake-image-bytes";

        $upload = $this->post('/admin/assets', $token, [
            'content_type' => 'image/png',
            'data_base64' => base64_encode($pngBytes),
        ]);
        self::assertSame(201, $upload->status);
        $asset = $this->decode($upload->body);
        self::assertSame('image', $asset['kind']);
        self::assertStringStartsWith('/public/assets/', $asset['asset_url']);

        // Public serve returns the exact bytes with the stored content type.
        $id = $asset['id'];
        $serve = $this->kernel->handle(new Request('GET', '/public/assets/' . $id));
        self::assertSame(200, $serve->status);
        self::assertSame('image/png', $serve->headers['Content-Type']);
        self::assertSame('nosniff', $serve->headers['X-Content-Type-Options']);
        self::assertSame($pngBytes, $serve->body);
    }

    public function testUnsupportedTypeIsRejected(): void
    {
        $token = $this->login('editor@acme.test');
        // SVG is script-capable and not allowlisted.
        $response = $this->post('/admin/assets', $token, [
            'content_type' => 'image/svg+xml',
            'data_base64' => base64_encode('<svg onload="alert(1)"></svg>'),
        ]);
        self::assertSame(422, $response->status);
        self::assertStringEndsWith('/problems/asset-invalid', $this->decode($response->body)['type']);
    }

    public function testUnknownAssetIs404(): void
    {
        $response = $this->kernel->handle(new Request('GET', '/public/assets/ast-nope'));
        self::assertSame(404, $response->status);
        self::assertStringEndsWith('/problems/asset-not-found', $this->decode($response->body)['type']);
    }

    public function testUploadRequiresManageCreatives(): void
    {
        $token = $this->login('analyst@acme.test');
        $response = $this->post('/admin/assets', $token, [
            'content_type' => 'image/png',
            'data_base64' => base64_encode('x'),
        ]);
        self::assertSame(403, $response->status);
    }

    /** @param array<string, mixed> $body */
    private function post(string $path, string $token, array $body): Response
    {
        return $this->kernel->handle(new Request('POST', $path, ['authorization' => 'Bearer ' . $token], [], (string) json_encode($body)));
    }

    private function login(string $email): string
    {
        $response = $this->kernel->handle(new Request('POST', '/admin/login', [], [], (string) json_encode([
            'organization' => 'acme', 'email' => $email, 'password' => DevFixtures::PASSWORD,
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
