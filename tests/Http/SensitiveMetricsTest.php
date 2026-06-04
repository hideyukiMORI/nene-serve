<?php

declare(strict_types=1);

namespace NeneServe\Tests\Http;

use NeneServe\Audit\InMemoryAuditLog;
use NeneServe\Http\Kernel;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Measurement\InMemoryEventStore;
use NeneServe\Serving\Token\InMemoryTokenStore;
use NeneServe\Support\DevFixtures;
use PHPUnit\Framework\TestCase;

/**
 * Sensitive-read audit (ADR 0022 §4, measurement-spec): `include_sensitive`
 * exposes the per-visitor_bucket breakdown, requires `view_sensitive_metrics`,
 * and is audited. Ordinary aggregate reads are not audited.
 */
final class SensitiveMetricsTest extends TestCase
{
    public function testSensitiveReadIsGatedAndAudited(): void
    {
        $audit = new InMemoryAuditLog();
        $events = new InMemoryEventStore();
        $kernel = new Kernel(tokens: new InMemoryTokenStore(), events: $events, audit: $audit);

        // Generate a consenting impression so there is a visitor_bucket to expose.
        $serve = $this->decode($kernel->handle(new Request(
            'GET',
            '/public/placements/pk_acme_home/serve',
            ['user-agent' => 'ua'],
            ['consent' => 'granted'],
            '',
            [],
            '203.0.113.5',
        ))->body);
        $kernel->handle(new Request(
            'POST',
            '/public/events/impression',
            ['user-agent' => 'ua'],
            [],
            (string) json_encode(['impression_token' => $serve['impression_token'], 'consent_state' => 'granted']),
            [],
            '203.0.113.5',
        ));

        $admin = $this->login($kernel, 'admin@acme.test');

        // Ordinary aggregate read — no sensitive section, no audit.
        $plain = $this->get($kernel, '/admin/metrics?from=2026-01-01&to=2026-12-31', $admin);
        self::assertSame(200, $plain->status);
        self::assertArrayNotHasKey('sensitive', $this->decode($plain->body));
        self::assertSame([], $audit->allForOrganization('org-acme'), 'aggregate read must not audit');

        // Sensitive read — visitor breakdown + audited.
        $sensitive = $this->get($kernel, '/admin/metrics?from=2026-01-01&to=2026-12-31&include_sensitive=true', $admin);
        self::assertSame(200, $sensitive->status);
        $body = $this->decode($sensitive->body);
        self::assertArrayHasKey('sensitive', $body);
        self::assertNotEmpty($body['sensitive']);
        self::assertArrayHasKey('visitor_bucket', $body['sensitive'][0]);

        $actions = array_map(static fn ($e) => $e->action, $audit->allForOrganization('org-acme'));
        self::assertContains('metrics.read_sensitive', $actions);
    }

    public function testAnalystCannotReadSensitive(): void
    {
        $kernel = new Kernel();
        $analyst = $this->login($kernel, 'analyst@acme.test'); // has view_metrics, not view_sensitive_metrics

        // Aggregate is allowed…
        self::assertSame(200, $this->get($kernel, '/admin/metrics', $analyst)->status);
        // …sensitive is not.
        $denied = $this->get($kernel, '/admin/metrics?include_sensitive=true', $analyst);
        self::assertSame(403, $denied->status);
        self::assertStringEndsWith('/problems/insufficient-capability', $this->decode($denied->body)['type']);
    }

    private function get(Kernel $kernel, string $path, string $token): Response
    {
        $parts = explode('?', $path, 2);
        parse_str($parts[1] ?? '', $query);
        $stringQuery = [];
        foreach ($query as $k => $v) {
            $stringQuery[(string) $k] = is_array($v) ? '' : (string) $v;
        }

        return $kernel->handle(new Request('GET', $parts[0], ['authorization' => 'Bearer ' . $token], $stringQuery));
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
