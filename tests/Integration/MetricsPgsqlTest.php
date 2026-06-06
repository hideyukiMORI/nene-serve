<?php

declare(strict_types=1);

namespace NeneServe\Tests\Integration;

use Nene2\Database\PdoDatabaseQueryExecutor;
use NeneServe\Measurement\PdoEventStore;
use NeneServe\Support\SqlDialect;
use NeneServe\Tests\Support\PgsqlTestDatabase;
use NeneServe\Tests\Support\TestDatabase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Runs the daily-metrics query against real PostgreSQL with the PG dialect
 * (CAST(... AS date), which MySQL/SQLite express as DATE()). Skips when no PG is
 * configured (PG support, issue #120).
 */
#[Group('integration')]
final class MetricsPgsqlTest extends TestCase
{
    private PdoDatabaseQueryExecutor $db;

    private string $organizationId;

    protected function setUp(): void
    {
        $db = PgsqlTestDatabase::fromEnv();

        if ($db === null) {
            self::markTestSkipped('PostgreSQL integration DB not configured (set PGSQL_TEST_HOST).');
        }

        $this->db = $db;
        $this->organizationId = 'it-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        if (isset($this->db)) {
            foreach (['impressions', 'clicks'] as $table) {
                $this->db->execute("DELETE FROM {$table} WHERE organization_id = ?", [$this->organizationId]);
            }
        }
    }

    public function testDailyMetricsAggregatesOnPostgres(): void
    {
        $org = $this->organizationId;

        TestDatabase::seed($this->db, 'impressions', $this->event('i1', $org, '2026-06-01 09:00:00'));
        TestDatabase::seed($this->db, 'impressions', $this->event('i2', $org, '2026-06-01 21:30:00'));
        TestDatabase::seed($this->db, 'impressions', $this->event('i3', $org, '2026-06-02 08:00:00'));
        TestDatabase::seed($this->db, 'clicks', $this->event('k1', $org, '2026-06-01 12:00:00'));

        $rows = (new PdoEventStore($this->db, SqlDialect::Pgsql))->dailyMetrics($org, '2026-06-01', '2026-06-02');

        self::assertCount(2, $rows);
        self::assertSame('2026-06-01', $rows[0]->date);
        self::assertSame(2, $rows[0]->impressions);
        self::assertSame(1, $rows[0]->clicks);
        self::assertSame('2026-06-02', $rows[1]->date);
        self::assertSame(1, $rows[1]->impressions);
        self::assertSame(0, $rows[1]->clicks);
    }

    /** @return array<string, scalar|null> */
    private function event(string $id, string $org, string $at): array
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
