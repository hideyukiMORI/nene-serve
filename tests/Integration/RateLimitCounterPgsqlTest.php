<?php

declare(strict_types=1);

namespace NeneServe\Tests\Integration;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeneServe\Http\RateLimit\PdoRateLimitStorage;
use NeneServe\Support\SqlDialect;
use NeneServe\Tests\Support\FixedClock;
use NeneServe\Tests\Support\PgsqlTestDatabase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * The rate limit counter on real PostgreSQL (PG support, issue #120). SQLite
 * takes the same `ON CONFLICT` branch, but not with native prepared statements
 * or PG's type strictness — the translated DDL stores the window as BIGINT and
 * the comparison must stay an integer comparison.
 */
#[Group('integration')]
final class RateLimitCounterPgsqlTest extends TestCase
{
    private DatabaseQueryExecutorInterface $db;

    private string $key;

    protected function setUp(): void
    {
        $db = PgsqlTestDatabase::fromEnv();

        if ($db === null) {
            self::markTestSkipped('PostgreSQL integration DB not configured (set PGSQL_TEST_HOST).');
        }

        $this->db = $db;
        $this->key = 'ip:198.51.100.' . random_int(1, 254) . '/' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        if (isset($this->db)) {
            $this->db->execute('DELETE FROM rate_limit_counters WHERE counter_key = ?', [hash('sha256', $this->key)]);
        }
    }

    public function testCounterAccumulatesAcrossBootsAndRestartsAfterTheWindowOnPostgres(): void
    {
        $atNoon = new FixedClock('2026-08-04T12:00:00+00:00');

        $first = $this->boot($atNoon)->hit($this->key, 60);
        $second = $this->boot($atNoon)->hit($this->key, 60);

        self::assertSame(1, $first['count']);
        self::assertSame(2, $second['count']);
        self::assertSame($first['reset_at'], $second['reset_at']);

        $afterWindow = $this->boot(new FixedClock('2026-08-04T12:01:01+00:00'))->hit($this->key, 60);

        self::assertSame(1, $afterWindow['count']);
        self::assertGreaterThan($first['reset_at'], $afterWindow['reset_at']);
    }

    private function boot(FixedClock $clock): PdoRateLimitStorage
    {
        return new PdoRateLimitStorage($this->db, SqlDialect::Pgsql, $clock);
    }
}
