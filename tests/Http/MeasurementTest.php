<?php

declare(strict_types=1);

namespace NeneServe\Tests\Http;

use NeneServe\Http\Kernel;
use NeneServe\Http\Request;
use NeneServe\Measurement\InMemoryEventStore;
use NeneServe\Serving\Token\InMemoryTokenStore;
use NeneServe\Support\DevFixtures;
use PHPUnit\Framework\TestCase;

/**
 * End-to-end measurement (measurement-spec, privacy §3/§4): serve → impression
 * → click are counted; opt-out placements are not; the CSV export is aggregated
 * and tenant-scoped; consent gates the hashed visitor bucket.
 */
final class MeasurementTest extends TestCase
{
    public function testServeImpressionClickAreCountedAndExported(): void
    {
        $events = new InMemoryEventStore();
        $kernel = new Kernel(tokens: new InMemoryTokenStore(), events: $events);

        $serve = $this->decode($kernel->handle(new Request('GET', '/public/placements/pk_acme_home/serve'))->body);
        $clickToken = substr((string) $serve['click_url'], strlen('/public/clicks/'));

        // Beacon the impression (idempotent: twice → still one), then click.
        $beacon = (string) json_encode(['impression_token' => $serve['impression_token'], 'consent_state' => 'granted']);
        $kernel->handle(new Request('POST', '/public/events/impression', [], [], $beacon));
        $kernel->handle(new Request('POST', '/public/events/impression', [], [], $beacon));
        self::assertSame(302, $kernel->handle(new Request('GET', '/public/clicks/' . $clickToken))->status);

        $rows = $events->dailyMetrics('org-acme', gmdate('Y-m-d'), gmdate('Y-m-d'));
        self::assertCount(1, $rows);
        self::assertSame(1, $rows[0]->impressions, 'replay must not inflate');
        self::assertSame(1, $rows[0]->clicks);
    }

    public function testOptedOutPlacementServesButCountsNothing(): void
    {
        $events = new InMemoryEventStore();
        $kernel = new Kernel(tokens: new InMemoryTokenStore(), events: $events);

        $serve = $kernel->handle(new Request('GET', '/public/placements/pk_acme_quiet/serve'));
        self::assertSame(200, $serve->status);
        $body = $this->decode($serve->body);

        // No beacon token issued (privacy P2); click still works.
        self::assertArrayNotHasKey('impression_token', $body);
        $clickToken = substr((string) $body['click_url'], strlen('/public/clicks/'));
        self::assertSame(302, $kernel->handle(new Request('GET', '/public/clicks/' . $clickToken))->status);

        self::assertSame([], $events->dailyMetrics('org-acme', gmdate('Y-m-d'), gmdate('Y-m-d')));
    }

    public function testExportRequiresViewMetricsAndIsCsv(): void
    {
        $kernel = new Kernel();
        $token = $this->login($kernel, 'analyst@acme.test'); // analyst is read-only metrics

        $response = $kernel->handle(new Request('GET', '/admin/metrics/export', ['authorization' => 'Bearer ' . $token]));
        self::assertSame(200, $response->status);
        self::assertStringContainsString('text/csv', $response->headers['Content-Type'] ?? '');
        self::assertStringStartsWith('date,placement_id,creative_id,impressions,clicks,ctr', $response->body);
    }

    public function testServiceExportRequiresReadMetricsScope(): void
    {
        // The fixture service token only has read:placements, not read:metrics.
        $response = (new Kernel())->handle(new Request(
            'GET',
            '/api/metrics/export',
            ['authorization' => 'Bearer ' . DevFixtures::SERVICE_TOKEN],
        ));

        self::assertSame(403, $response->status);
        self::assertStringEndsWith('/problems/insufficient-scope', $this->decode($response->body)['type']);
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
