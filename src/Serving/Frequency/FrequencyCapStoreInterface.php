<?php

declare(strict_types=1);

namespace NeneServe\Serving\Frequency;

/**
 * Counts impressions already delivered to a consent-gated `visitor_bucket` for a
 * placement. The bucket rotates per UTC day (see VisitorBucket), so counts are a
 * per-day window automatically. Only consulted/incremented when consent permits
 * a bucket (privacy ADR 0017 §3); never tracks without consent.
 */
interface FrequencyCapStoreInterface
{
    public function count(string $placementId, string $visitorBucket): int;

    public function increment(string $placementId, string $visitorBucket): void;
}
