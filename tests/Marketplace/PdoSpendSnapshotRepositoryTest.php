<?php

declare(strict_types=1);

namespace NeneServe\Tests\Marketplace;

use Nene2\Database\PdoDatabaseQueryExecutor;
use NeneServe\Marketplace\PdoSpendSnapshotRepository;
use NeneServe\Marketplace\SpendSnapshot;
use NeneServe\Tests\Support\TestDatabase;
use PHPUnit\Framework\TestCase;

/**
 * Spend snapshots are append-only/immutable — re-deriving writes a NEW version,
 * never an overwrite (billing §7). The repository's save() is a portable INSERT,
 * so this is a full round-trip test of versioning, latest-wins reads, and tenant
 * scoping against in-memory SQLite.
 */
final class PdoSpendSnapshotRepositoryTest extends TestCase
{
    private PdoDatabaseQueryExecutor $db;
    private PdoSpendSnapshotRepository $repo;

    protected function setUp(): void
    {
        $this->db = TestDatabase::withSchema('spend_snapshots');
        $this->repo = new PdoSpendSnapshotRepository($this->db);
    }

    private function snapshot(int $version, string $org = 'org-1', string $period = 'bp-1', int $spent = 12_500): SpendSnapshot
    {
        return new SpendSnapshot(
            'ss-' . $org . '-' . $period . '-' . $version,
            $org,
            $period,
            $version,
            1000,
            50,
            'pr-1',
            2,
            $spent,
            'hash-' . $version,
            '2026-06-06T00:00:00+00:00',
        );
    }

    public function testSaveAndReadBackLatest(): void
    {
        $this->repo->save($this->snapshot(1, spent: 100));

        $latest = $this->repo->latestForPeriod('org-1', 'bp-1');
        self::assertNotNull($latest);
        self::assertSame(1, $latest->version);
        self::assertSame(100, $latest->spentCents);
        self::assertSame('hash-1', $latest->hash);
    }

    public function testCurrentVersionStartsAtZeroThenTracksMax(): void
    {
        self::assertSame(0, $this->repo->currentVersion('org-1', 'bp-1'));

        $this->repo->save($this->snapshot(1));
        $this->repo->save($this->snapshot(2));

        self::assertSame(2, $this->repo->currentVersion('org-1', 'bp-1'));
    }

    public function testLatestReturnsHighestVersionRegardlessOfInsertOrder(): void
    {
        $this->repo->save($this->snapshot(2, spent: 200));
        $this->repo->save($this->snapshot(1, spent: 100));
        $this->repo->save($this->snapshot(3, spent: 300));

        $latest = $this->repo->latestForPeriod('org-1', 'bp-1');
        self::assertNotNull($latest);
        self::assertSame(3, $latest->version);
        self::assertSame(300, $latest->spentCents);
    }

    public function testReadsAreTenantAndPeriodScoped(): void
    {
        $this->repo->save($this->snapshot(1, org: 'org-1', period: 'bp-1'));
        $this->repo->save($this->snapshot(1, org: 'org-2', period: 'bp-1'));
        $this->repo->save($this->snapshot(1, org: 'org-1', period: 'bp-2'));

        self::assertNull($this->repo->latestForPeriod('org-1', 'bp-missing'));
        self::assertSame(0, $this->repo->currentVersion('org-OTHER', 'bp-1'));
        self::assertNotNull($this->repo->latestForPeriod('org-1', 'bp-2'));
    }
}
