<?php

declare(strict_types=1);

namespace NeneServe\Tests\Http;

use NeneServe\Http\Kernel;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Serving\Token\InMemoryTokenStore;
use NeneServe\Support\DevFixtures;
use PHPUnit\Framework\TestCase;

/**
 * HTML5 bundle (ADR 0020/0021 §4): acceptance + content policy, malware scan
 * gate, sandboxed-frame strict CSP, and the review queue.
 */
final class Html5CreativeTest extends TestCase
{
    private Kernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = new Kernel(tokens: new InMemoryTokenStore());
    }

    public function testCleanBundleApprovesAndServesInSandboxedFrame(): void
    {
        $editor = $this->login('editor@acme.test');
        $admin = $this->login('admin@acme.test');

        $creativeId = $this->decode($this->post('/admin/creatives', $editor, $this->cleanBundle())->body)['id'];

        $key = 'pk_h5_' . substr($creativeId, -6);
        $this->post('/admin/placements', $admin, [
            'public_placement_key' => $key, 'allowed_origins' => [],
            'default_creative_id' => $creativeId, 'status' => 'active',
        ]);

        $this->post('/admin/creatives/' . $creativeId . '/submit', $editor, []);
        $this->post('/admin/creatives/' . $creativeId . '/start-review', $admin, []);
        self::assertSame(200, $this->post('/admin/creatives/' . $creativeId . '/approve', $admin, [])->status);

        $serve = $this->decode($this->kernel->handle(new Request('GET', '/public/placements/' . $key . '/serve'))->body);
        self::assertSame('html5_bundle', $serve['creative']['type']);
        self::assertSame('iframe_sandboxed', $serve['creative']['render']['mode']);
        $frameUrl = (string) $serve['creative']['render']['frame_url'];
        self::assertStringStartsWith('/public/frames/', $frameUrl);

        $frame = $this->kernel->handle(new Request('GET', $frameUrl));
        self::assertSame(200, $frame->status);
        $csp = $frame->headers['Content-Security-Policy'] ?? '';
        self::assertStringContainsString('sandbox', $csp);
        self::assertStringContainsString("script-src 'self'", $csp);
        self::assertStringNotContainsString('unsafe-eval', $csp);
    }

    public function testFlaggedBundleCannotBeSubmitted(): void
    {
        $editor = $this->login('editor@acme.test');

        $bundle = $this->cleanBundle();
        $bundle['html_entry'] = '<html><body>EICAR-STANDARD-ANTIVIRUS-TEST-FILE</body></html>';
        $created = $this->post('/admin/creatives', $editor, $bundle);
        self::assertSame(201, $created->status);
        self::assertSame('flagged', $this->decode($created->body)['scan_status']);

        $id = $this->decode($created->body)['id'];
        $submit = $this->post('/admin/creatives/' . $id . '/submit', $editor, []);
        self::assertSame(422, $submit->status);
        self::assertStringEndsWith('/problems/creative-scan-failed', $this->decode($submit->body)['type']);
    }

    public function testContentPolicyRejectsRemoteScriptAndEval(): void
    {
        $editor = $this->login('editor@acme.test');

        $remoteScript = $this->cleanBundle();
        $remoteScript['html_entry'] = '<html><head><script src="https://evil.test/x.js"></script></head></html>';
        self::assertSame(422, $this->post('/admin/creatives', $editor, $remoteScript)->status);

        $withEval = $this->cleanBundle();
        $withEval['html_entry'] = '<html><body><script>eval("x")</script></body></html>';
        self::assertSame(422, $this->post('/admin/creatives', $editor, $withEval)->status);
    }

    public function testThirdPartyTagTypeIsForbidden(): void
    {
        $editor = $this->login('editor@acme.test');

        $response = $this->post('/admin/creatives', $editor, ['type' => 'third_party_tag']);
        self::assertSame(422, $response->status);
    }

    public function testReviewQueueListsSubmittedCreatives(): void
    {
        $editor = $this->login('editor@acme.test');
        $admin = $this->login('admin@acme.test');

        $id = $this->decode($this->post('/admin/creatives', $editor, $this->cleanBundle())->body)['id'];
        $this->post('/admin/creatives/' . $id . '/submit', $editor, []);

        $queue = $this->decode($this->kernel->handle(new Request('GET', '/admin/review-queue', ['authorization' => 'Bearer ' . $admin]))->body);
        $ids = array_column($queue['creatives'], 'id');
        self::assertContains($id, $ids);
    }

    /** @return array<string, mixed> */
    private function cleanBundle(): array
    {
        return [
            'type' => 'html5_bundle',
            'destination_url' => 'https://acme.test/landing',
            'bundle_id' => 'bndl-' . bin2hex(random_bytes(3)),
            'bundle_size_bytes' => 50_000,
            'asset_count' => 5,
            'html_entry' => '<html><body><a href="#">ad</a></body></html>',
            'width' => 300,
            'height' => 250,
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
