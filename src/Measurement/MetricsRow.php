<?php

declare(strict_types=1);

namespace NeneServe\Measurement;

/** One aggregated daily metrics bucket (per date / placement / creative). */
final class MetricsRow
{
    public function __construct(
        public readonly string $date,
        public readonly string $placementId,
        public readonly string $creativeId,
        public readonly int $impressions,
        public readonly int $clicks,
    ) {
    }

    public function ctr(): float
    {
        return $this->impressions === 0 ? 0.0 : $this->clicks / $this->impressions;
    }
}
