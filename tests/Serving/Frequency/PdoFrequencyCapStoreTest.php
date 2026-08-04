<?php

declare(strict_types=1);

namespace NeneServe\Tests\Serving\Frequency;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeneServe\Serving\Frequency\PdoFrequencyCapStore;
use NeneServe\Tests\Support\TestDatabase;
use PHPUnit\Framework\TestCase;

/**
 * The shared frequency store counts impression events instead of keeping a
 * counter beside them, so the cap can never disagree with the numbers billing
 * reads (ADR 0015). These tests pin that equivalence — including the cases where
 * a separate counter would have drifted.
 */
final class PdoFrequencyCapStoreTest extends TestCase
{
    public function testCountsImpressionsForThatPlacementAndVisitorOnly(): void
    {
        $db = $this->db();
        $this->impression($db, 'i1', 'plc-1', 'bucket-a');
        $this->impression($db, 'i2', 'plc-1', 'bucket-a');
        $this->impression($db, 'i3', 'plc-1', 'bucket-b'); // another visitor
        $this->impression($db, 'i4', 'plc-2', 'bucket-a'); // another placement

        self::assertSame(2, $this->store($db)->count('plc-1', 'bucket-a'));
    }

    public function testAVisitorWithNoImpressionsCountsZero(): void
    {
        self::assertSame(0, $this->store($this->db())->count('plc-1', 'bucket-a'));
    }

    public function testErasedImpressionsDropOut(): void
    {
        $db = $this->db();
        $this->impression($db, 'i1', 'plc-1', 'bucket-a');
        $this->impression($db, 'i2', 'plc-1', 'bucket-a', erasedAt: '2026-08-04 12:00:00');

        // Erasure is an additive tombstone that also forgets the visitor link
        // (privacy §5); an erased row must not hold a cap against anyone.
        self::assertSame(1, $this->store($db)->count('plc-1', 'bucket-a'));
    }

    public function testImpressionsWithoutAConsentBucketAreNotCounted(): void
    {
        $db = $this->db();
        $this->impression($db, 'i1', 'plc-1', null); // consent denied — no bucket
        $this->impression($db, 'i2', 'plc-1', 'bucket-a');

        self::assertSame(1, $this->store($db)->count('plc-1', 'bucket-a'));
    }

    public function testIncrementDoesNotWriteASecondNumber(): void
    {
        $db = $this->db();
        $this->impression($db, 'i1', 'plc-1', 'bucket-a');

        $store = $this->store($db);
        $store->increment('plc-1', 'bucket-a');
        $store->increment('plc-1', 'bucket-a');

        // The impression row *is* the count. If increment wrote anywhere, the
        // cap would run ahead of the billing numbers — the exact drift the
        // derived count exists to prevent.
        self::assertSame(1, $store->count('plc-1', 'bucket-a'));
    }

    private function db(): DatabaseQueryExecutorInterface
    {
        return TestDatabase::withSchema('impressions');
    }

    private function store(DatabaseQueryExecutorInterface $db): PdoFrequencyCapStore
    {
        return new PdoFrequencyCapStore($db);
    }

    private function impression(
        DatabaseQueryExecutorInterface $db,
        string $id,
        string $placementId,
        ?string $bucket,
        ?string $erasedAt = null,
    ): void {
        TestDatabase::seed($db, 'impressions', [
            'id' => $id,
            'organization_id' => 'org-1',
            'placement_id' => $placementId,
            'creative_id' => 'crv-1',
            'occurred_at' => '2026-08-04 12:00:00',
            'visitor_bucket' => $bucket,
            'erased_at' => $erasedAt,
        ]);
    }
}
