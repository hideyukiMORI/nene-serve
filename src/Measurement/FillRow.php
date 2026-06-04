<?php

declare(strict_types=1);

namespace NeneServe\Measurement;

/** Daily fill-rate bucket per date/placement (measurement-spec). */
final class FillRow
{
    public function __construct(
        public readonly string $date,
        public readonly string $placementId,
        public readonly int $serveRequests,
        public readonly int $fills,
    ) {
    }

    /** Fills (non-fallback serves) / serve requests. */
    public function fillRate(): float
    {
        return $this->serveRequests === 0 ? 0.0 : $this->fills / $this->serveRequests;
    }
}
