<?php

declare(strict_types=1);

namespace NeneServe\Measurement\UseCase;

use Nene2\Http\RequestScopedHolder;
use NeneServe\Measurement\CsvMetricsFormatter;
use NeneServe\Measurement\EventStoreInterface;

/**
 * Daily metrics CSV for one tenant (measurement-spec reporting). Aggregated only
 * — no raw visitor identifiers — so it is safe for the admin and service (and,
 * in marketplace mode, advertiser) consumers (privacy N8).
 */
final readonly class ExportMetricsUseCase
{
    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private EventStoreInterface $events,
        private RequestScopedHolder $organizationId,
    ) {
    }

    public function csv(string $fromDate, string $toDate): string
    {
        return CsvMetricsFormatter::format(
            $this->events->dailyMetrics($this->organizationId->get(), $fromDate, $toDate),
        );
    }
}
