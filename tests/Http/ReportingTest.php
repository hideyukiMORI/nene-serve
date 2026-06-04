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
 * JSON time-series reporting (measurement-spec): CTR per creative + fill rate per
 * placement, aggregated and tenant-scoped (privacy N8).
 */
final class ReportingTest extends TestCase
{
    public function testMetricsReportHasCtrAndFillRate(): void
    {
        $events = new InMemoryEventStore();
        $kernel = new Kernel(tokens: new InMemoryTokenStore(), events: $events);

        // One filled serve on pk_acme_home (+ impression + click)…
        $serve = $this->decode($kernel->handle(new Request('GET', '/public/placements/pk_acme_home/serve'))->body);
        $kernel->handle(new Request('POST', '/public/events/impression', [], [], (string) json_encode(['impression_token' => $serve['impression_token']])));
        $clickToken = substr((string) $serve['click_url'], strlen('/public/clicks/'));
        $kernel->handle(new Request('GET', '/public/clicks/' . $clickToken));

        // …and one empty serve (draft creative placement) → a serve request that did not fill.
        $kernel->handle(new Request('GET', '/public/placements/pk_acme_side/serve'));

        $token = $this->login($kernel, 'analyst@acme.test');
        $report = $this->decode($kernel->handle(new Request(
            'GET',
            '/admin/metrics',
            ['authorization' => 'Bearer ' . $token],
            ['from' => '2026-01-01', 'to' => '2026-12-31'],
        ))->body);

        // CTR row for the served creative.
        $row = $this->rowFor($report['rows'], 'cr-acme-banner');
        self::assertSame(1, $row['impressions']);
        self::assertSame(1, $row['clicks']);
        self::assertEqualsWithDelta(1.0, (float) $row['ctr'], 0.0001);

        // Fill rate: pk_acme_home filled (1/1 = 1.0), pk_acme_side did not (0/1 = 0.0).
        $home = $this->fillFor($report['fill'], 'plc-acme-home');
        self::assertEqualsWithDelta(1.0, (float) $home['fill_rate'], 0.0001);
        $side = $this->fillFor($report['fill'], 'plc-acme-side');
        self::assertEqualsWithDelta(0.0, (float) $side['fill_rate'], 0.0001);
    }

    public function testServiceMetricsNeedsReadMetricsScope(): void
    {
        $response = (new Kernel())->handle(new Request(
            'GET',
            '/api/metrics',
            ['authorization' => 'Bearer ' . DevFixtures::SERVICE_TOKEN],
        ));

        self::assertSame(403, $response->status);
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array<string, mixed>
     */
    private function rowFor(array $rows, string $creativeId): array
    {
        foreach ($rows as $row) {
            if (($row['creative_id'] ?? null) === $creativeId) {
                return $row;
            }
        }
        self::fail('no row for ' . $creativeId);
    }

    /**
     * @param list<array<string, mixed>> $fill
     * @return array<string, mixed>
     */
    private function fillFor(array $fill, string $placementId): array
    {
        foreach ($fill as $row) {
            if (($row['placement_id'] ?? null) === $placementId) {
                return $row;
            }
        }
        self::fail('no fill row for ' . $placementId);
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
