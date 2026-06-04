<?php

declare(strict_types=1);

namespace NeneServe\Tests\Http;

use NeneServe\Http\Kernel;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Measurement\InMemoryEventStore;
use NeneServe\Serving\Token\InMemoryTokenStore;
use NeneServe\Support\DevFixtures;
use PHPUnit\Framework\TestCase;

/**
 * Concierge conversion beacon (ADR 0009): append-only conversion attributed to a
 * placement, surfaced in aggregated reporting; no Contact submission; opt-out
 * aware; unknown placement 404.
 */
final class ConversionBeaconTest extends TestCase
{
    public function testConversionRecordedAndAggregated(): void
    {
        $events = new InMemoryEventStore();
        $kernel = new Kernel(events: $events, tokens: new InMemoryTokenStore());

        self::assertSame(204, $this->beacon($kernel, 'pk_acme_home')->status);
        self::assertSame(204, $this->beacon($kernel, 'pk_acme_home')->status);

        // Surfaced in aggregated reporting (admin metrics), no raw identifiers.
        $admin = $this->login($kernel, 'admin@acme.test');
        $report = $this->decode($kernel->handle(new Request(
            'GET',
            '/admin/metrics',
            ['authorization' => 'Bearer ' . $admin],
            ['from' => gmdate('Y-m-d'), 'to' => gmdate('Y-m-d')],
        ))->body);

        self::assertArrayHasKey('conversions', $report);
        $row = $report['conversions'][0];
        self::assertSame('plc-acme-home', $row['placement_id']);
        self::assertSame(2, $row['conversions']);
    }

    public function testUnknownPlacementIs404(): void
    {
        $kernel = new Kernel(tokens: new InMemoryTokenStore());

        $response = $this->beacon($kernel, 'pk_nope');
        self::assertSame(404, $response->status);
        self::assertStringEndsWith('/problems/placement-not-found', $this->decode($response->body)['type']);
    }

    public function testOptedOutPlacementRecordsNothing(): void
    {
        $events = new InMemoryEventStore();
        $kernel = new Kernel(events: $events, tokens: new InMemoryTokenStore());

        // pk_acme_quiet has measurement_enabled=false.
        self::assertSame(204, $this->beacon($kernel, 'pk_acme_quiet')->status);
        self::assertSame([], $events->dailyConversions('org-acme', gmdate('Y-m-d'), gmdate('Y-m-d')));
    }

    public function testRequiresPlacementKey(): void
    {
        $kernel = new Kernel(tokens: new InMemoryTokenStore());
        $response = $kernel->handle(new Request('POST', '/public/events/conversion', [], [], '{}'));
        self::assertSame(422, $response->status);
    }

    private function beacon(Kernel $kernel, string $key): Response
    {
        return $kernel->handle(new Request('POST', '/public/events/conversion', [], [], (string) json_encode(['public_placement_key' => $key])));
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
