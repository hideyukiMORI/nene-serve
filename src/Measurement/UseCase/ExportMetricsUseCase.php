<?php

declare(strict_types=1);

namespace NeneServe\Measurement\UseCase;

use NeneServe\Measurement\CsvMetricsFormatter;
use NeneServe\Measurement\EventStoreInterface;

/**
 * Daily metrics CSV for one tenant (measurement-spec reporting). Aggregated only
 * — no raw visitor identifiers — so it is safe for the admin and service (and,
 * in marketplace mode, advertiser) consumers (privacy N8).
 */
final class ExportMetricsUseCase
{
    public function __construct(
        private readonly EventStoreInterface $events,
    ) {
    }

    public function csv(string $organizationId, string $fromDate, string $toDate): string
    {
        return CsvMetricsFormatter::format(
            $this->events->dailyMetrics($organizationId, $fromDate, $toDate),
        );
    }
}
