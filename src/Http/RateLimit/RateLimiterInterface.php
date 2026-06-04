<?php

declare(strict_types=1);

namespace NeneServe\Http\RateLimit;

/**
 * Per-key request limiter for the public surface (ADR 0010/0019). Exceeding a
 * limit MUST return 429 with a reason code and never silently drop a metric.
 */
interface RateLimiterInterface
{
    /** Returns false when the caller has exhausted its budget for the window. */
    public function allow(string $key): bool;
}
