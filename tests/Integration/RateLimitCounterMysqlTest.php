<?php

declare(strict_types=1);

namespace NeneServe\Tests\Integration;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeneServe\Http\RateLimit\PdoRateLimitStorage;
use NeneServe\Support\SqlDialect;
use NeneServe\Tests\Support\FixedClock;
use NeneServe\Tests\Support\MysqlTestDatabase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * The MySQL branch of the rate limit counter's conditional upsert
 * (`INSERT … AS new ON DUPLICATE KEY UPDATE … IF(…)`), which the SQLite unit
 * tests never execute — they take the `ON CONFLICT` path. #199 was a control
 * that was mounted but could not fire; a dialect branch that is never run in the
 * dialect production uses would be the same failure wearing a different hat.
 */
#[Group('integration')]
final class RateLimitCounterMysqlTest extends TestCase
{
    private DatabaseQueryExecutorInterface $db;

    private string $key;

    protected function setUp(): void
    {
        $db = MysqlTestDatabase::fromEnv();

        if ($db === null) {
            self::markTestSkipped('MySQL integration DB not configured (set MYSQL_TEST_HOST).');
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

    public function testCounterAccumulatesAcrossBootsAndRestartsAfterTheWindowOnMysql(): void
    {
        $atNoon = new FixedClock('2026-08-04T12:00:00+00:00');

        $first = $this->boot($atNoon)->hit($this->key, 60);
        $second = $this->boot($atNoon)->hit($this->key, 60);
        $third = $this->boot(new FixedClock('2026-08-04T12:00:59+00:00'))->hit($this->key, 60);

        self::assertSame(1, $first['count']);
        self::assertSame(2, $second['count']);
        self::assertSame(3, $third['count']);
        self::assertSame($first['reset_at'], $third['reset_at'], 'The window end holds until it passes.');

        // One second past the reset: new window, count back to 1.
        $fourth = $this->boot(new FixedClock('2026-08-04T12:01:01+00:00'))->hit($this->key, 60);

        self::assertSame(1, $fourth['count']);
        self::assertGreaterThan($first['reset_at'], $fourth['reset_at']);
    }

    public function testTheStoredKeyIsHashedOnMysql(): void
    {
        $this->boot(new FixedClock())->hit($this->key, 60);

        $row = $this->db->fetchOne(
            'SELECT counter_key FROM rate_limit_counters WHERE counter_key = ?',
            [hash('sha256', $this->key)],
        );

        self::assertNotNull($row, 'The counter is stored under the hash of the key.');
    }

    private function boot(FixedClock $clock): PdoRateLimitStorage
    {
        return new PdoRateLimitStorage($this->db, SqlDialect::Mysql, $clock);
    }
}
