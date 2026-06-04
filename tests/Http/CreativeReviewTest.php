<?php

declare(strict_types=1);

namespace NeneServe\Tests\Http;

use NeneServe\Http\Kernel;
use NeneServe\Http\Request;
use NeneServe\Support\DevFixtures;
use PHPUnit\Framework\TestCase;

/**
 * Creative review approval gate (ADR 0020/0021): the full lifecycle, four-eyes,
 * capability gating, image acceptance, and the headline guarantee — an image
 * serves only once approved.
 */
final class CreativeReviewTest extends TestCase
{
    private Kernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = new Kernel();
    }

    public function testApprovedImageServesOnlyAfterApproval(): void
    {
        $editor = $this->login('editor@acme.test');
        $admin = $this->login('admin@acme.test');

        // Editor creates an image creative (draft) and a placement defaulting to it.
        $creativeId = $this->decode($this->post('/admin/creatives', $editor, [
            'destination_url' => 'https://acme.test/landing',
            'asset_url' => 'https://cdn.acme.test/promo.png',
            'width' => 300,
            'height' => 250,
        ])->body)['id'];

        $key = 'pk_review_' . substr($creativeId, -6);
        $this->post('/admin/placements', $admin, [
            'public_placement_key' => $key,
            'allowed_origins' => [],
            'default_creative_id' => $creativeId,
            'status' => 'active',
        ]);

        // Before approval: nothing serves (204).
        self::assertSame(204, $this->serve($key)->status);

        // Walk the state machine: editor submits, admin reviews + approves (four-eyes).
        self::assertSame(200, $this->post('/admin/creatives/' . $creativeId . '/submit', $editor, [])->status);
        self::assertSame(200, $this->post('/admin/creatives/' . $creativeId . '/start-review', $admin, [])->status);
        self::assertSame(200, $this->post('/admin/creatives/' . $creativeId . '/approve', $admin, [])->status);

        // After approval: it serves.
        $serve = $this->serve($key);
        self::assertSame(200, $serve->status);
        self::assertSame('https://cdn.acme.test/promo.png', $this->decode($serve->body)['creative']['asset_url']);
    }

    public function testSelfApprovalIsForbiddenWithoutOverride(): void
    {
        $admin = $this->login('admin@acme.test');
        $creativeId = $this->createSubmittedCreative($admin); // admin is the submitter

        $this->post('/admin/creatives/' . $creativeId . '/start-review', $admin, []);

        $denied = $this->post('/admin/creatives/' . $creativeId . '/approve', $admin, []);
        self::assertSame(403, $denied->status);
        self::assertStringEndsWith('/problems/self-approval-forbidden', $this->decode($denied->body)['type']);

        // Audited override permits it.
        $ok = $this->post('/admin/creatives/' . $creativeId . '/approve', $admin, ['self_approval_override' => true]);
        self::assertSame(200, $ok->status);
        self::assertSame('approved', $this->decode($ok->body)['review_status']);
    }

    public function testIllegalTransitionIs409(): void
    {
        $editor = $this->login('editor@acme.test');
        $admin = $this->login('admin@acme.test');
        $creativeId = $this->decode($this->post('/admin/creatives', $editor, $this->validImage())->body)['id'];

        // Approve straight from draft — illegal.
        $response = $this->post('/admin/creatives/' . $creativeId . '/approve', $admin, []);
        self::assertSame(409, $response->status);
        self::assertStringEndsWith('/problems/invalid-review-transition', $this->decode($response->body)['type']);
    }

    public function testImageAcceptanceRejectsBadAsset(): void
    {
        $editor = $this->login('editor@acme.test');

        $response = $this->post('/admin/creatives', $editor, [
            'destination_url' => 'https://acme.test/landing',
            'asset_url' => 'http://evil.test/x.exe', // not https, not an image
            'width' => 300,
            'height' => 250,
        ]);

        self::assertSame(422, $response->status);
        self::assertStringEndsWith('/problems/validation-failed', $this->decode($response->body)['type']);
    }

    public function testAnalystCannotCreateCreative(): void
    {
        $analyst = $this->login('analyst@acme.test');

        $response = $this->post('/admin/creatives', $analyst, $this->validImage());

        self::assertSame(403, $response->status);
        self::assertStringEndsWith('/problems/insufficient-capability', $this->decode($response->body)['type']);
    }

    public function testRejectRequiresReason(): void
    {
        $editor = $this->login('editor@acme.test');
        $admin = $this->login('admin@acme.test');
        $creativeId = $this->createSubmittedCreative($editor);
        $this->post('/admin/creatives/' . $creativeId . '/start-review', $admin, []);

        $noReason = $this->post('/admin/creatives/' . $creativeId . '/reject', $admin, []);
        self::assertSame(422, $noReason->status);

        $withReason = $this->post('/admin/creatives/' . $creativeId . '/reject', $admin, ['review_reason' => 'off-brand']);
        self::assertSame(200, $withReason->status);
        self::assertSame('rejected', $this->decode($withReason->body)['review_status']);
    }

    private function createSubmittedCreative(string $token): string
    {
        $id = $this->decode($this->post('/admin/creatives', $token, $this->validImage())->body)['id'];
        $this->post('/admin/creatives/' . $id . '/submit', $token, []);

        return $id;
    }

    /** @return array<string, mixed> */
    private function validImage(): array
    {
        return [
            'destination_url' => 'https://acme.test/landing',
            'asset_url' => 'https://cdn.acme.test/promo.png',
            'width' => 300,
            'height' => 250,
        ];
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

    /** @param array<string, mixed> $body */
    private function post(string $path, string $token, array $body): \NeneServe\Http\Response
    {
        return $this->kernel->handle(new Request(
            'POST',
            $path,
            ['authorization' => 'Bearer ' . $token],
            [],
            (string) json_encode($body),
        ));
    }

    private function serve(string $key): \NeneServe\Http\Response
    {
        return $this->kernel->handle(new Request('GET', '/public/placements/' . $key . '/serve'));
    }

    /** @return array<string, mixed> */
    private function decode(string $json): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($json, true);

        return $decoded;
    }
}
