<?php

declare(strict_types=1);

namespace NeneServe\Tests\Http;

use NeneServe\Http\Kernel;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Measurement\InMemoryEventStore;
use NeneServe\Measurement\VisitorBucket;
use NeneServe\Serving\Token\InMemoryTokenStore;
use NeneServe\Support\DevFixtures;
use PHPUnit\Framework\TestCase;

/**
 * DSR tooling (privacy §5): a consenting visitor's data can be exported and then
 * erased as an additive tombstone — the aggregate counts must not change.
 */
final class DataSubjectRequestTest extends TestCase
{
    public function testExportThenErasureKeepsCountsButForgetsVisitor(): void
    {
        $events = new InMemoryEventStore();
        $kernel = new Kernel(tokens: new InMemoryTokenStore(), events: $events);

        // A consenting visitor generates one impression (bucket derived from IP+UA).
        $serve = $this->decode($this->serve($kernel)->body);
        $this->beacon($kernel, (string) $serve['impression_token']);

        $bucket = VisitorBucket::derive('org-acme', '198.51.100.9', 'dsr-agent');
        $admin = $this->login($kernel, 'admin@acme.test');

        // Export returns the visitor's impression.
        $export = $this->decode($this->dsr($kernel, $admin, ['kind' => 'export', 'visitor_bucket' => $bucket])->body);
        self::assertCount(1, $export['records']);

        // Metrics before erasure.
        $before = $events->dailyMetrics('org-acme', gmdate('Y-m-d'), gmdate('Y-m-d'));
        self::assertSame(1, $before[0]->impressions);

        // Erase: additive tombstone — one row tombstoned.
        $erase = $this->decode($this->dsr($kernel, $admin, ['kind' => 'erasure', 'visitor_bucket' => $bucket])->body);
        self::assertSame(1, $erase['tombstoned']);

        // Counts unchanged; the visitor link is forgotten so export is now empty.
        $after = $events->dailyMetrics('org-acme', gmdate('Y-m-d'), gmdate('Y-m-d'));
        self::assertSame(1, $after[0]->impressions, 'erasure must not edit counts');
        $exportAfter = $this->decode($this->dsr($kernel, $admin, ['kind' => 'export', 'visitor_bucket' => $bucket])->body);
        self::assertCount(0, $exportAfter['records']);
    }

    public function testDsrRequiresManageSettings(): void
    {
        $kernel = new Kernel();
        $analyst = $this->login($kernel, 'analyst@acme.test');

        $response = $this->dsr($kernel, $analyst, ['kind' => 'export', 'visitor_bucket' => 'x']);
        self::assertSame(403, $response->status);
        self::assertStringEndsWith('/problems/insufficient-capability', $this->decode($response->body)['type']);
    }

    public function testInvalidKindIsRejected(): void
    {
        $kernel = new Kernel();
        $admin = $this->login($kernel, 'admin@acme.test');

        self::assertSame(422, $this->dsr($kernel, $admin, ['kind' => 'nope', 'visitor_bucket' => 'x'])->status);
    }

    private function serve(Kernel $kernel): Response
    {
        return $kernel->handle(new Request(
            'GET',
            '/public/placements/pk_acme_home/serve',
            ['user-agent' => 'dsr-agent'],
            ['consent' => 'granted'],
            '',
            [],
            '198.51.100.9',
        ));
    }

    private function beacon(Kernel $kernel, string $token): void
    {
        $body = (string) json_encode(['impression_token' => $token, 'consent_state' => 'granted']);
        $kernel->handle(new Request('POST', '/public/events/impression', ['user-agent' => 'dsr-agent'], [], $body, [], '198.51.100.9'));
    }

    /** @param array<string, mixed> $body */
    private function dsr(Kernel $kernel, string $token, array $body): Response
    {
        return $kernel->handle(new Request(
            'POST',
            '/admin/data-subject-requests',
            ['authorization' => 'Bearer ' . $token],
            [],
            (string) json_encode($body),
        ));
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
