<?php

declare(strict_types=1);

namespace NeneServe\Tests\Http;

use NeneServe\Audit\AuditChainVerifier;
use NeneServe\Audit\InMemoryAuditLog;
use NeneServe\Http\Kernel;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Support\DevFixtures;
use PHPUnit\Framework\TestCase;

/**
 * The archive lifecycle is a governed mutation, so it must be audited with
 * before→after and stay on the tamper-evident chain (ADR 0022 §2/§3).
 */
final class PlacementArchiveAuditTest extends TestCase
{
    public function testArchiveIsAuditedWithBeforeAfter(): void
    {
        $audit = new InMemoryAuditLog();
        $kernel = new Kernel(audit: $audit);
        $admin = $this->login($kernel, 'admin@acme.test');

        // Create a placement, then archive it.
        $created = $this->decode($this->post($kernel, '/admin/placements', $admin, [
            'public_placement_key' => 'pk_arch', 'allowed_origins' => [], 'status' => 'active',
        ])->body);
        $id = (string) $created['id'];

        $archived = $this->post($kernel, '/admin/placements/' . $id . '/archive', $admin, []);
        self::assertSame(200, $archived->status);
        self::assertSame('archived', $this->decode($archived->body)['status']);

        // The archive emitted an audited event with before/after.
        $events = $audit->forSubject('org-acme', 'placement', $id);
        $actions = array_map(static fn ($e) => $e->action, $events);
        self::assertContains('placement.archived', $actions);

        $archiveEvent = $events[0]; // newest first
        self::assertSame('placement.archived', $archiveEvent->action);
        self::assertSame('active', $archiveEvent->metadata['before']['status']);
        self::assertSame('archived', $archiveEvent->metadata['after']['status']);

        // The org chain (create + archive) still verifies.
        self::assertTrue(AuditChainVerifier::verify($audit->allForOrganization('org-acme')));
    }

    public function testArchiveUnknownPlacementIs404(): void
    {
        $kernel = new Kernel();
        $admin = $this->login($kernel, 'admin@acme.test');

        $response = $this->post($kernel, '/admin/placements/nope/archive', $admin, []);
        self::assertSame(404, $response->status);
    }

    private function login(Kernel $kernel, string $email): string
    {
        $body = (string) json_encode(['organization' => 'acme', 'email' => $email, 'password' => DevFixtures::PASSWORD]);

        return (string) $this->decode($kernel->handle(new Request('POST', '/admin/login', [], [], $body))->body)['token'];
    }

    /** @param array<string, mixed> $body */
    private function post(Kernel $kernel, string $path, string $token, array $body): Response
    {
        return $kernel->handle(new Request('POST', $path, ['authorization' => 'Bearer ' . $token], [], (string) json_encode($body)));
    }

    /** @return array<string, mixed> */
    private function decode(string $json): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($json, true);

        return $decoded;
    }
}
