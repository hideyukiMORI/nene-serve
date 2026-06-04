<?php

declare(strict_types=1);

namespace NeneServe\Tests\Http;

use NeneServe\Http\Kernel;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Support\DevFixtures;
use PHPUnit\Framework\TestCase;

/**
 * Video creative acceptance (ADR 0021 §3): validation, the shared review gate,
 * and a serve payload that carries a poster with autoplay-with-sound disabled.
 */
final class VideoCreativeTest extends TestCase
{
    private Kernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = new Kernel();
    }

    public function testApprovedVideoServesWithPosterAndNoAutoplay(): void
    {
        $editor = $this->login('editor@acme.test');
        $admin = $this->login('admin@acme.test');

        $creativeId = $this->decode($this->post('/admin/creatives', $editor, $this->validVideo())->body)['id'];

        $key = 'pk_vid_' . substr($creativeId, -6);
        $this->post('/admin/placements', $admin, [
            'public_placement_key' => $key,
            'allowed_origins' => [],
            'default_creative_id' => $creativeId,
            'status' => 'active',
        ]);

        // Walk to approved (editor submits, admin approves — four-eyes).
        $this->post('/admin/creatives/' . $creativeId . '/submit', $editor, []);
        $this->post('/admin/creatives/' . $creativeId . '/start-review', $admin, []);
        self::assertSame(200, $this->post('/admin/creatives/' . $creativeId . '/approve', $admin, [])->status);

        $serve = $this->decode($this->kernel->handle(new Request('GET', '/public/placements/' . $key . '/serve'))->body);
        self::assertSame('video', $serve['creative']['type']);
        self::assertSame('https://cdn.acme.test/promo.mp4', $serve['creative']['asset_url']);
        self::assertSame('https://cdn.acme.test/poster.jpg', $serve['creative']['poster_url']);
        self::assertFalse($serve['creative']['autoplay']);
    }

    public function testVideoRejectsBadFormatAndMissingPoster(): void
    {
        $editor = $this->login('editor@acme.test');

        $badFormat = $this->post('/admin/creatives', $editor, [
            'type' => 'video',
            'destination_url' => 'https://acme.test/landing',
            'asset_url' => 'https://cdn.acme.test/promo.mov', // not mp4/webm
            'poster_url' => 'https://cdn.acme.test/poster.jpg',
            'width' => 640, 'height' => 360, 'duration_seconds' => 20,
        ]);
        self::assertSame(422, $badFormat->status);

        $tooLong = $this->post('/admin/creatives', $editor, [
            'type' => 'video',
            'destination_url' => 'https://acme.test/landing',
            'asset_url' => 'https://cdn.acme.test/promo.mp4',
            'poster_url' => 'https://cdn.acme.test/poster.jpg',
            'width' => 640, 'height' => 360, 'duration_seconds' => 9999,
        ]);
        self::assertSame(422, $tooLong->status);
    }

    /** @return array<string, mixed> */
    private function validVideo(): array
    {
        return [
            'type' => 'video',
            'destination_url' => 'https://acme.test/landing',
            'asset_url' => 'https://cdn.acme.test/promo.mp4',
            'poster_url' => 'https://cdn.acme.test/poster.jpg',
            'width' => 640,
            'height' => 360,
            'duration_seconds' => 20,
        ];
    }

    private function login(string $email): string
    {
        $body = (string) json_encode(['organization' => 'acme', 'email' => $email, 'password' => DevFixtures::PASSWORD]);

        return (string) $this->decode($this->kernel->handle(new Request('POST', '/admin/login', [], [], $body))->body)['token'];
    }

    /** @param array<string, mixed> $body */
    private function post(string $path, string $token, array $body): Response
    {
        return $this->kernel->handle(new Request('POST', $path, ['authorization' => 'Bearer ' . $token], [], (string) json_encode($body)));
    }

    /** @return array<string, mixed> */
    private function decode(string $json): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($json, true);

        return $decoded;
    }
}
