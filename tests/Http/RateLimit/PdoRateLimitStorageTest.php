<?php

declare(strict_types=1);

namespace NeneServe\Tests\Http\RateLimit;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Http\ClockInterface;
use Nene2\Middleware\InMemoryRateLimitStorage;
use Nene2\Middleware\RateLimitStorageInterface;
use NeneServe\Http\RateLimit\PdoRateLimitStorage;
use NeneServe\Http\RateLimit\RateLimitStorageException;
use NeneServe\Support\SqlDialect;
use NeneServe\Tests\Support\FixedClock;
use NeneServe\Tests\Support\TestDatabase;
use PHPUnit\Framework\TestCase;

/**
 * The guard for #199. The defect was not a wrong number — it was a control that
 * could never fire, while every gate stayed green. So the central test simulates
 * what production actually does (a fresh container per request) and asserts the
 * limit is reachable; a positive control runs the identical simulation against
 * the store that shipped, and proves it does not reach the limit. Without that
 * control, the test could pass for reasons unrelated to sharing.
 */
final class PdoRateLimitStorageTest extends TestCase
{
    private const LIMIT = 5;

    public function testCounterAdvancesAcrossContainerBootsUntilTheLimitTrips(): void
    {
        $query = TestDatabase::withSchema('rate_limit_counters');
        $clock = new FixedClock('2026-08-04T12:00:00+00:00');

        $counts = [];

        // Each iteration builds a new storage object over the same database —
        // one PHP process per request, exactly as public_html/index.php runs.
        for ($request = 1; $request <= self::LIMIT + 1; $request++) {
            $counts[] = $this->boot($query, $clock)->hit('ip:198.51.100.7', 60)['count'];
        }

        self::assertSame([1, 2, 3, 4, 5, 6], $counts);
        self::assertGreaterThan(self::LIMIT, end($counts), 'The limit must be reachable across boots.');
    }

    public function testTheReplacedInMemoryStoreFailsThatSameSimulation(): void
    {
        $counts = [];

        for ($request = 1; $request <= self::LIMIT + 1; $request++) {
            $counts[] = (new InMemoryRateLimitStorage())->hit('ip:198.51.100.7', 60)['count'];
        }

        // This is the bug: the counter is rebuilt empty every request, so the
        // budget is unreachable at any request volume (#199).
        self::assertSame([1, 1, 1, 1, 1, 1], $counts);
        self::assertLessThanOrEqual(self::LIMIT, max($counts));
    }

    public function testWindowRestartsOnceItHasExpired(): void
    {
        $query = TestDatabase::withSchema('rate_limit_counters');

        $first = $this->boot($query, new FixedClock('2026-08-04T12:00:00+00:00'))->hit('ip:203.0.113.9', 60);
        $second = $this->boot($query, new FixedClock('2026-08-04T12:00:30+00:00'))->hit('ip:203.0.113.9', 60);

        self::assertSame(1, $first['count']);
        self::assertSame(2, $second['count'], 'Within the window the count accumulates.');
        self::assertSame($first['reset_at'], $second['reset_at'], 'The window end does not move mid-window.');

        // One second past the reset the window starts over.
        $third = $this->boot($query, new FixedClock('2026-08-04T12:01:01+00:00'))->hit('ip:203.0.113.9', 60);

        self::assertSame(1, $third['count']);
        self::assertGreaterThan($first['reset_at'], $third['reset_at']);
    }

    public function testResetAtIsTheEndOfTheCurrentWindow(): void
    {
        $query = TestDatabase::withSchema('rate_limit_counters');
        $clock = new FixedClock('2026-08-04T12:00:00+00:00');

        $result = $this->boot($query, $clock)->hit('ip:203.0.113.10', 60);

        self::assertSame($clock->now()->getTimestamp() + 60, $result['reset_at']);
    }

    public function testEachKeyCountsSeparately(): void
    {
        $query = TestDatabase::withSchema('rate_limit_counters');
        $clock = new FixedClock();

        $this->boot($query, $clock)->hit('ip:198.51.100.1', 60);
        $this->boot($query, $clock)->hit('ip:198.51.100.1', 60);
        $other = $this->boot($query, $clock)->hit('ip:198.51.100.2', 60);

        self::assertSame(1, $other['count']);
    }

    public function testTheRawKeyIsNeverWrittenToTheDatabase(): void
    {
        $query = TestDatabase::withSchema('rate_limit_counters');
        $key = 'ip:198.51.100.42';

        $this->boot($query, new FixedClock())->hit($key, 60);

        $rows = $query->fetchAll('SELECT counter_key FROM rate_limit_counters');

        self::assertCount(1, $rows);
        self::assertSame(hash('sha256', $key), $rows[0]['counter_key']);
        self::assertStringNotContainsString('198.51.100.42', (string) $rows[0]['counter_key']);
    }

    public function testAVanishedCounterFailsClosedInsteadOfReportingAnEmptyWindow(): void
    {
        $storage = new PdoRateLimitStorage($this->writeOnlyExecutor(), SqlDialect::Sqlite, new FixedClock());

        $this->expectException(RateLimitStorageException::class);

        $storage->hit('ip:198.51.100.7', 60);
    }

    /**
     * A new storage instance over the same database — the object graph a fresh
     * container boot produces.
     */
    private function boot(DatabaseQueryExecutorInterface $query, ClockInterface $clock): RateLimitStorageInterface
    {
        return new PdoRateLimitStorage($query, SqlDialect::Sqlite, $clock);
    }

    /** An executor that accepts writes and then cannot find them. */
    private function writeOnlyExecutor(): DatabaseQueryExecutorInterface
    {
        return new class () implements DatabaseQueryExecutorInterface {
            public function execute(string $sql, array $parameters = []): int
            {
                return 1;
            }

            public function insert(string $sql, array $parameters = []): int
            {
                return 0;
            }

            public function lastInsertId(): int
            {
                return 0;
            }

            public function fetchOne(string $sql, array $parameters = []): ?array
            {
                return null;
            }

            public function fetchAll(string $sql, array $parameters = []): array
            {
                return [];
            }
        };
    }
}
