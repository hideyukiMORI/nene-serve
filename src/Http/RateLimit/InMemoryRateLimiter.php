<?php

declare(strict_types=1);

namespace NeneServe\Http\RateLimit;

/**
 * Fixed-window in-memory limiter. Single-process only (per `php -S` worker);
 * production swaps a shared store (Redis) behind the same interface. `$now` is
 * injectable for deterministic tests.
 */
final class InMemoryRateLimiter implements RateLimiterInterface
{
    /** @var array<string, array{count: int, window: int}> */
    private array $buckets = [];

    /** @var callable(): int */
    private $now;

    /** @param (callable(): int)|null $now */
    public function __construct(
        private readonly int $limit = 120,
        private readonly int $windowSeconds = 60,
        ?callable $now = null,
    ) {
        $this->now = $now ?? static fn (): int => time();
    }

    public function allow(string $key): bool
    {
        $window = intdiv(($this->now)(), $this->windowSeconds);
        $bucket = $this->buckets[$key] ?? null;

        if ($bucket === null || $bucket['window'] !== $window) {
            $this->buckets[$key] = ['count' => 1, 'window' => $window];

            return true;
        }

        if ($bucket['count'] >= $this->limit) {
            return false;
        }

        ++$this->buckets[$key]['count'];

        return true;
    }
}
