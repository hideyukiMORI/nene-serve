<?php

declare(strict_types=1);

namespace NeneServe\Http\RateLimit;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Http\ClockInterface;
use Nene2\Http\UtcClock;
use Nene2\Middleware\RateLimitStorageInterface;
use NeneServe\Support\SqlDialect;

/**
 * Fixed-window rate limit counters in the database, shared by every process and
 * host that talks to it.
 *
 * This exists because the framework's in-memory store cannot enforce a limit
 * under serve's deployment shape: the container is rebuilt per request, so the
 * counter never advanced past 1 and the 600/60s budget was unreachable at any
 * request volume (#199). Rate limiting is binding, not decorative — ADR 0019 §5
 * and api-security §63 require it on all three surfaces.
 *
 * **Increment is one atomic statement**, so concurrent requests cannot lose a
 * hit to a read-then-write race. The count is then read back in a second
 * statement; under concurrency that read can already include a later request's
 * increment, so the returned count is never *lower* than this request's own —
 * the limiter errs toward denying, which is the direction api-security §28
 * requires ("an exhausted rate limit denies; it never falls back to a more
 * permissive path").
 *
 * **The stored key is a SHA-256 hash**, never the middleware's raw key. The
 * default key extractor uses the client IP, and the in-memory store it replaces
 * never persisted anything; hashing keeps that property while moving the counter
 * to disk (ADR 0016/0017 — data minimisation, no raw addresses at rest). The
 * hash is a minimisation measure, not a security boundary: anyone who can read
 * this table can confirm a guessed address.
 *
 * The window is stored as a Unix timestamp rather than a datetime so the
 * expiry comparison is an integer comparison in every dialect.
 */
final readonly class PdoRateLimitStorage implements RateLimitStorageInterface
{
    private const TABLE = 'rate_limit_counters';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private SqlDialect $dialect = SqlDialect::Mysql,
        private ClockInterface $clock = new UtcClock(),
    ) {
    }

    public function hit(string $key, int $windowSeconds): array
    {
        $storedKey = hash('sha256', $key);
        $now = $this->clock->now()->getTimestamp();
        $windowEnd = $now + max(1, $windowSeconds);

        // Insert at 1, or — in the same statement — either restart the window if
        // it has already passed, or add to the running count.
        $this->query->execute($this->hitSql(), [$storedKey, $windowEnd, $now, $now]);

        $row = $this->query->fetchOne(
            'SELECT hit_count, window_reset_at FROM ' . self::TABLE . ' WHERE counter_key = ?',
            [$storedKey],
        );

        if ($row === null) {
            throw new RateLimitStorageException(
                'Rate limit counter vanished immediately after it was written; refusing to report an '
                . 'empty window, which would let the request through unthrottled.',
            );
        }

        return [
            'count' => (int) $row['hit_count'],
            'reset_at' => (int) $row['window_reset_at'],
        ];
    }

    /**
     * The conditional upsert. `SqlDialect::upsert()` writes the plain
     * overwrite-on-conflict form; this needs the update side to branch on the
     * stored window, so the two dialect shapes are spelled out here.
     *
     * Bind order for both: counter_key, window end (new window), now, now.
     */
    private function hitSql(): string
    {
        $table = self::TABLE;
        $insert = "INSERT INTO {$table} (counter_key, hit_count, window_reset_at) VALUES (?, 1, ?)";

        if ($this->dialect === SqlDialect::Mysql) {
            return "{$insert} AS new ON DUPLICATE KEY UPDATE
                hit_count = IF({$table}.window_reset_at <= ?, 1, {$table}.hit_count + 1),
                window_reset_at = IF({$table}.window_reset_at <= ?, new.window_reset_at, {$table}.window_reset_at)";
        }

        return "{$insert} ON CONFLICT (counter_key) DO UPDATE SET
            hit_count = CASE WHEN {$table}.window_reset_at <= ? THEN 1 ELSE {$table}.hit_count + 1 END,
            window_reset_at = CASE WHEN {$table}.window_reset_at <= ? THEN excluded.window_reset_at
                ELSE {$table}.window_reset_at END";
    }
}
