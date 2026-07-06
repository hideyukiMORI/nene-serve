<?php

declare(strict_types=1);

namespace NeneServe\Tests\Support;

use DateTimeImmutable;
use Nene2\Http\ClockInterface;

/**
 * A {@see ClockInterface} that always returns a fixed instant, so tests that
 * depend on the current time (token expiry, `exp`/`iat` claims, invitation TTLs)
 * are deterministic instead of racing the wall clock.
 */
final readonly class FixedClock implements ClockInterface
{
    public function __construct(private string $instant = '2026-07-06T09:00:00+00:00')
    {
    }

    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->instant);
    }
}
