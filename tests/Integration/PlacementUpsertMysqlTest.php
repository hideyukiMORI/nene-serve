<?php

declare(strict_types=1);

namespace NeneServe\Tests\Integration;

use Nene2\Database\PdoDatabaseQueryExecutor;
use NeneServe\Serving\PdoPlacementRepository;
use NeneServe\Serving\Placement;
use NeneServe\Support\SqlDialect;
use NeneServe\Tests\Support\MysqlTestDatabase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Exercises the SqlDialect upsert on real MySQL with native prepared statements:
 * `INSERT … AS new ON DUPLICATE KEY UPDATE`. SQLite/PG use the ON CONFLICT path
 * (covered elsewhere); this proves the MySQL branch round-trips end to end.
 */
#[Group('integration')]
final class PlacementUpsertMysqlTest extends TestCase
{
    private PdoDatabaseQueryExecutor $db;

    private string $organizationId;

    private string $placementKey;

    protected function setUp(): void
    {
        $db = MysqlTestDatabase::fromEnv();

        if ($db === null) {
            self::markTestSkipped('MySQL integration DB not configured (set MYSQL_TEST_HOST).');
        }

        $this->db = $db;
        $this->organizationId = 'it-' . bin2hex(random_bytes(6));
        $this->placementKey = 'pk_' . bin2hex(random_bytes(6));

        // placements FK-references organizations (ON DELETE RESTRICT); create a
        // throwaway tenant for this test.
        $this->db->execute(
            'INSERT INTO organizations (id, slug, name) VALUES (?, ?, ?)',
            [$this->organizationId, 'it-' . bin2hex(random_bytes(6)), 'Integration Test'],
        );
    }

    protected function tearDown(): void
    {
        if (isset($this->db)) {
            $this->db->execute('DELETE FROM placements WHERE organization_id = ?', [$this->organizationId]);
            $this->db->execute('DELETE FROM organizations WHERE id = ?', [$this->organizationId]);
        }
    }

    public function testSaveInsertsThenUpsertsOnMysql(): void
    {
        $repo = new PdoPlacementRepository($this->db, SqlDialect::Mysql);
        $id = 'plc-' . bin2hex(random_bytes(6));

        $repo->save(new Placement($id, $this->organizationId, $this->placementKey, ['https://a.com'], 'draft'));
        $created = $repo->findByPublicKey($this->placementKey);
        self::assertNotNull($created);
        self::assertSame('draft', $created->status);

        $repo->save(new Placement($id, $this->organizationId, $this->placementKey, ['https://a.com', 'https://b.com'], 'active', null, false, 3));
        $updated = $repo->findByPublicKey($this->placementKey);

        self::assertNotNull($updated);
        self::assertSame('active', $updated->status);
        self::assertSame(['https://a.com', 'https://b.com'], $updated->allowedOrigins);
        self::assertFalse($updated->measurementEnabled);
        self::assertSame(3, $updated->frequencyCap);
    }
}
