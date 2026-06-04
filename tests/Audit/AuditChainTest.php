<?php

declare(strict_types=1);

namespace NeneServe\Tests\Audit;

use NeneServe\Audit\AuditChainVerifier;
use NeneServe\Audit\AuditEvent;
use NeneServe\Audit\AuditHasher;
use NeneServe\Audit\InMemoryAuditLog;
use PHPUnit\Framework\TestCase;

/**
 * Tamper-evident audit chain (ADR 0022 §5): a clean chain verifies; any edit,
 * deletion, or reorder breaks it. Chains are per tenant.
 */
final class AuditChainTest extends TestCase
{
    public function testRecordedChainVerifies(): void
    {
        $log = new InMemoryAuditLog();
        $log->record('org-1', 'u1', 'creative.created', 'creative', 'c1', ['type' => 'image']);
        $log->record('org-1', 'u1', 'creative.submit', 'creative', 'c1');
        $log->record('org-1', 'u2', 'creative.approve', 'creative', 'c1');

        $events = $log->allForOrganization('org-1');
        self::assertCount(3, $events);
        self::assertSame('', $events[0]->previousHash, 'first row chains from genesis');
        self::assertSame($events[0]->hash, $events[1]->previousHash);
        self::assertSame($events[1]->hash, $events[2]->previousHash);
        self::assertTrue(AuditChainVerifier::verify($events));
    }

    public function testEditedMetadataBreaksChain(): void
    {
        $log = new InMemoryAuditLog();
        $log->record('org-1', 'u1', 'budget.changed', 'budget', 'b1', ['cents' => 1000]);
        $log->record('org-1', 'u1', 'budget.changed', 'budget', 'b1', ['cents' => 2000]);
        $events = $log->allForOrganization('org-1');

        // Tamper with the first record's metadata, keeping its stored hash.
        $tampered = $events;
        $orig = $events[0];
        $tampered[0] = new AuditEvent(
            $orig->id,
            $orig->organizationId,
            $orig->actorUserId,
            $orig->action,
            $orig->subjectType,
            $orig->subjectId,
            ['cents' => 999_999],
            $orig->occurredAt,
            $orig->previousHash,
            $orig->hash,
        );

        self::assertFalse(AuditChainVerifier::verify($tampered));
    }

    public function testDeletedRowBreaksChain(): void
    {
        $log = new InMemoryAuditLog();
        $log->record('org-1', 'u1', 'a.x', 's', '1');
        $log->record('org-1', 'u1', 'a.y', 's', '1');
        $log->record('org-1', 'u1', 'a.z', 's', '1');
        $events = $log->allForOrganization('org-1');

        // Remove the middle row — the survivor's previousHash no longer links.
        $gapped = [$events[0], $events[2]];
        self::assertFalse(AuditChainVerifier::verify($gapped));
    }

    public function testChainsAreScopedPerTenant(): void
    {
        $log = new InMemoryAuditLog();
        $log->record('org-1', 'u1', 'a.x', 's', '1');
        $log->record('org-2', 'u9', 'a.x', 's', '1');
        $log->record('org-1', 'u1', 'a.y', 's', '1');

        self::assertTrue(AuditChainVerifier::verify($log->allForOrganization('org-1')));
        self::assertTrue(AuditChainVerifier::verify($log->allForOrganization('org-2')));
        // org-2's single row chains from genesis, independent of org-1.
        self::assertSame('', $log->allForOrganization('org-2')[0]->previousHash);
    }

    public function testHasherIsDeterministic(): void
    {
        $a = AuditHasher::compute('o', 'u', 'act', 's', 'id', ['k' => 'v'], '2026-06-04T00:00:00+00:00', 'prev');
        $b = AuditHasher::compute('o', 'u', 'act', 's', 'id', ['k' => 'v'], '2026-06-04T00:00:00+00:00', 'prev');
        self::assertSame($a, $b);
        self::assertSame(64, strlen($a));
    }
}
