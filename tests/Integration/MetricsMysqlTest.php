<?php

declare(strict_types=1);

namespace NeneServe\Tests\Integration;

use Nene2\Database\PdoDatabaseQueryExecutor;
use NeneServe\Measurement\PdoEventStore;
use NeneServe\Tests\Support\MysqlTestDatabase;
use NeneServe\Tests\Support\TestDatabase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Runs the daily-metrics query against a real MySQL with native prepared
 * statements — the exact path that 500'd in #116 (a named placeholder reused
 * across the impressions/clicks UNION → HY093). SQLite cannot reproduce it; this
 * suite skips entirely when no MySQL is configured.
 */
#[Group('integration')]
final class MetricsMysqlTest extends TestCase
{
    private PdoDatabaseQueryExecutor $db;

    private string $organizationId;

    protected function setUp(): void
    {
        $db = MysqlTestDatabase::fromEnv();

        if ($db === null) {
            self::markTestSkipped('MySQL integration DB not configured (set MYSQL_TEST_HOST).');
        }

        $this->db = $db;
        $this->organizationId = 'it-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        if (!isset($this->db)) {
            return;
        }

        foreach (['impressions', 'clicks'] as $table) {
            $this->db->execute("DELETE FROM {$table} WHERE organization_id = ?", [$this->organizationId]);
        }
    }

    public function testDailyMetricsAggregatesOnMysqlWithNativePrepares(): void
    {
        $org = $this->organizationId;

        TestDatabase::seed($this->db, 'impressions', $this->impression('i1', $org, '2026-06-01 09:00:00'));
        TestDatabase::seed($this->db, 'impressions', $this->impression('i2', $org, '2026-06-01 21:30:00'));
        TestDatabase::seed($this->db, 'impressions', $this->impression('i3', $org, '2026-06-02 08:00:00'));
        TestDatabase::seed($this->db, 'clicks', $this->click('k1', $org, '2026-06-01 12:00:00'));
        TestDatabase::seed($this->db, 'clicks', $this->click('k2', $org, '2026-06-09 12:00:00'));

        $rows = (new PdoEventStore($this->db))->dailyMetrics($org, '2026-06-01', '2026-06-02');

        self::assertCount(2, $rows);
        self::assertSame('2026-06-01', $rows[0]->date);
        self::assertSame(2, $rows[0]->impressions);
        self::assertSame(1, $rows[0]->clicks);
        self::assertSame('2026-06-02', $rows[1]->date);
        self::assertSame(1, $rows[1]->impressions);
        self::assertSame(0, $rows[1]->clicks);
    }

    /** @return array<string, scalar|null> */
    private function impression(string $id, string $org, string $at): array
    {
        return [
            'id' => $id . substr($org, 3, 8),
            'organization_id' => $org,
            'placement_id' => 'p1',
            'creative_id' => 'c1',
            'occurred_at' => $at,
        ];
    }

    /** @return array<string, scalar|null> */
    private function click(string $id, string $org, string $at): array
    {
        return [
            'id' => $id . substr($org, 3, 8),
            'organization_id' => $org,
            'placement_id' => 'p1',
            'creative_id' => 'c1',
            'occurred_at' => $at,
        ];
    }
}
