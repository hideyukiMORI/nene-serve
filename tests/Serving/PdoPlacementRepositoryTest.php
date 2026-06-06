<?php

declare(strict_types=1);

namespace NeneServe\Tests\Serving;

use Nene2\Database\PdoDatabaseQueryExecutor;
use NeneServe\Serving\PdoPlacementRepository;
use NeneServe\Serving\Placement;
use NeneServe\Support\SqlDialect;
use NeneServe\Tests\Support\TestDatabase;
use PHPUnit\Framework\TestCase;

/**
 * Exercises the real SELECT/hydration paths of PdoPlacementRepository against an
 * in-memory SQLite DB: JSON origin decoding, boolean/nullable casts, tenant
 * scoping, and ordered pagination.
 */
final class PdoPlacementRepositoryTest extends TestCase
{
    private PdoDatabaseQueryExecutor $db;
    private PdoPlacementRepository $repo;

    protected function setUp(): void
    {
        $this->db = TestDatabase::withSchema('placements');
        $this->repo = new PdoPlacementRepository($this->db);
    }

    /** @param array<string, scalar|null> $overrides */
    private function seedPlacement(array $overrides = []): void
    {
        TestDatabase::seed($this->db, 'placements', $overrides + [
            'id' => 'plc-1',
            'organization_id' => 'org-1',
            'public_placement_key' => 'pk_home',
            'allowed_origins' => '["https://a.com"]',
            'status' => 'active',
            'default_creative_id' => 'cr-1',
            'measurement_enabled' => 1,
            'frequency_cap' => 5,
            'archived_at' => null,
        ]);
    }

    public function testFindByPublicKeyHydratesEveryField(): void
    {
        $this->seedPlacement();

        $placement = $this->repo->findByPublicKey('pk_home');

        self::assertNotNull($placement);
        self::assertSame('plc-1', $placement->id);
        self::assertSame('org-1', $placement->organizationId);
        self::assertSame(['https://a.com'], $placement->allowedOrigins);
        self::assertSame('cr-1', $placement->defaultCreativeId);
        self::assertTrue($placement->measurementEnabled);
        self::assertSame(5, $placement->frequencyCap);
        self::assertNull($placement->archivedAt);
    }

    public function testHydratesNullableAndBooleanColumns(): void
    {
        $this->seedPlacement([
            'default_creative_id' => null,
            'measurement_enabled' => 0,
            'frequency_cap' => null,
            'allowed_origins' => '[]',
        ]);

        $placement = $this->repo->findByPublicKey('pk_home');

        self::assertNotNull($placement);
        self::assertNull($placement->defaultCreativeId);
        self::assertFalse($placement->measurementEnabled);
        self::assertNull($placement->frequencyCap);
        self::assertSame([], $placement->allowedOrigins);
    }

    public function testFindByPublicKeyReturnsNullWhenAbsent(): void
    {
        self::assertNull($this->repo->findByPublicKey('pk_missing'));
    }

    public function testFindByIdIsTenantScoped(): void
    {
        $this->seedPlacement();

        self::assertNotNull($this->repo->findByIdInOrganization('plc-1', 'org-1'));
        self::assertNull($this->repo->findByIdInOrganization('plc-1', 'org-OTHER'));
    }

    public function testListIsTenantScopedOrderedByKeyAndPaginated(): void
    {
        $this->seedPlacement(['id' => 'plc-b', 'public_placement_key' => 'pk_b']);
        $this->seedPlacement(['id' => 'plc-a', 'public_placement_key' => 'pk_a']);
        $this->seedPlacement(['id' => 'plc-c', 'public_placement_key' => 'pk_c']);
        $this->seedPlacement(['id' => 'plc-other', 'public_placement_key' => 'pk_z', 'organization_id' => 'org-2']);

        $keys = array_map(static fn ($p) => $p->publicPlacementKey, $this->repo->listByOrganization('org-1', 10, 0));
        self::assertSame(['pk_a', 'pk_b', 'pk_c'], $keys);

        $page = $this->repo->listByOrganization('org-1', 1, 1);
        self::assertCount(1, $page);
        self::assertSame('pk_b', $page[0]->publicPlacementKey);
    }

    public function testArchiveUpdatesStatusAndTimestamp(): void
    {
        $this->seedPlacement();

        $this->repo->archive('plc-1', 'org-1', '2026-06-06T00:00:00+00:00');

        $placement = $this->repo->findByPublicKey('pk_home');
        self::assertNotNull($placement);
        self::assertSame('archived', $placement->status);
        self::assertSame('2026-06-06T00:00:00+00:00', $placement->archivedAt);
        self::assertFalse($placement->isActive());
    }

    public function testArchiveIsTenantScoped(): void
    {
        $this->seedPlacement();

        $this->repo->archive('plc-1', 'org-OTHER', '2026-06-06T00:00:00+00:00');

        $placement = $this->repo->findByPublicKey('pk_home');
        self::assertNotNull($placement);
        self::assertSame('active', $placement->status);
    }

    public function testSaveInsertsThenUpsertsOnConflict(): void
    {
        // Exercises the SqlDialect upsert on a real engine (SQLite uses the same
        // ON CONFLICT path as PostgreSQL). save() creates, then updates in place.
        $repo = new PdoPlacementRepository($this->db, SqlDialect::Sqlite);

        $repo->save(new Placement('plc-1', 'org-1', 'pk_home', ['https://a.com'], 'draft', null, true, null, null));
        $created = $repo->findByPublicKey('pk_home');
        self::assertNotNull($created);
        self::assertSame('draft', $created->status);

        $repo->save(new Placement('plc-1', 'org-1', 'pk_home', ['https://a.com', 'https://b.com'], 'active', 'cr-9', false, 3, null));
        $updated = $repo->findByPublicKey('pk_home');

        self::assertNotNull($updated);
        self::assertSame('active', $updated->status);
        self::assertSame('cr-9', $updated->defaultCreativeId);
        self::assertSame(['https://a.com', 'https://b.com'], $updated->allowedOrigins);
        self::assertFalse($updated->measurementEnabled);
        self::assertSame(3, $updated->frequencyCap);
    }
}
