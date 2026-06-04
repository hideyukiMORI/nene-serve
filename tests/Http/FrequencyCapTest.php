<?php

declare(strict_types=1);

namespace NeneServe\Tests\Http;

use NeneServe\Http\Kernel;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Serving\Token\InMemoryTokenStore;
use PHPUnit\Framework\TestCase;

/**
 * Frequency cap (measurement-spec, privacy ADR 0017 §3): capped per consent-gated
 * visitor_bucket; without consent (or with measurement off) no cap applies and no
 * bucket is tracked (fail open to serve).
 */
final class FrequencyCapTest extends TestCase
{
    public function testCapSuppressesSecondServeForConsentingVisitor(): void
    {
        $kernel = new Kernel(tokens: new InMemoryTokenStore());

        // First serve (cap 1) succeeds; beacon the impression to count it.
        $first = $this->serve($kernel, 'pk_acme_capped', consent: true);
        self::assertSame(200, $first->status);
        $this->beacon($kernel, $this->decode($first->body)['impression_token']);

        // Same visitor (same IP/UA → same bucket) is now capped.
        $second = $this->serve($kernel, 'pk_acme_capped', consent: true);
        self::assertSame(204, $second->status);
    }

    public function testNoConsentMeansNoCap(): void
    {
        $kernel = new Kernel(tokens: new InMemoryTokenStore());

        // Without consent the bucket is never derived, so the cap cannot apply:
        // repeated serves keep returning a creative (and no bucket is tracked).
        self::assertSame(200, $this->serve($kernel, 'pk_acme_capped', consent: false)->status);
        self::assertSame(200, $this->serve($kernel, 'pk_acme_capped', consent: false)->status);
        self::assertSame(200, $this->serve($kernel, 'pk_acme_capped', consent: false)->status);
    }

    public function testUncappedPlacementIsNeverSuppressed(): void
    {
        $kernel = new Kernel(tokens: new InMemoryTokenStore());

        $first = $this->serve($kernel, 'pk_acme_home', consent: true);
        $this->beacon($kernel, $this->decode($first->body)['impression_token']);
        self::assertSame(200, $this->serve($kernel, 'pk_acme_home', consent: true)->status);
    }

    private function serve(Kernel $kernel, string $key, bool $consent): Response
    {
        $query = $consent ? ['consent' => 'granted'] : [];

        return $kernel->handle(new Request(
            'GET',
            '/public/placements/' . $key . '/serve',
            ['user-agent' => 'test-agent'],
            $query,
            '',
            [],
            '203.0.113.7',
        ));
    }

    private function beacon(Kernel $kernel, string $token): void
    {
        $body = (string) json_encode(['impression_token' => $token, 'consent_state' => 'granted']);
        $kernel->handle(new Request('POST', '/public/events/impression', ['user-agent' => 'test-agent'], [], $body, [], '203.0.113.7'));
    }

    /** @return array<string, mixed> */
    private function decode(string $json): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($json, true);

        return $decoded;
    }
}
