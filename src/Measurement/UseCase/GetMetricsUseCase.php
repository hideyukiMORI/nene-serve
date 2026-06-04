<?php

declare(strict_types=1);

namespace NeneServe\Measurement\UseCase;

use NeneServe\Measurement\EventStoreInterface;

/**
 * JSON daily time-series for one tenant (measurement-spec reporting): per
 * date/placement/creative impressions, clicks, CTR; plus per date/placement
 * fill rate. Aggregated only — no visitor identifiers (privacy N8).
 */
final class GetMetricsUseCase
{
    public function __construct(
        private readonly EventStoreInterface $events,
    ) {
    }

    /**
     * @return array{from: string, to: string, rows: list<array<string, mixed>>, fill: list<array<string, mixed>>}
     */
    public function report(string $organizationId, string $fromDate, string $toDate): array
    {
        $rows = array_map(
            static fn ($r): array => [
                'date' => $r->date,
                'placement_id' => $r->placementId,
                'creative_id' => $r->creativeId,
                'impressions' => $r->impressions,
                'clicks' => $r->clicks,
                'ctr' => round($r->ctr(), 4),
            ],
            $this->events->dailyMetrics($organizationId, $fromDate, $toDate),
        );

        $fill = array_map(
            static fn ($f): array => [
                'date' => $f->date,
                'placement_id' => $f->placementId,
                'serve_requests' => $f->serveRequests,
                'fills' => $f->fills,
                'fill_rate' => round($f->fillRate(), 4),
            ],
            $this->events->dailyFillRates($organizationId, $fromDate, $toDate),
        );

        return ['from' => $fromDate, 'to' => $toDate, 'rows' => $rows, 'fill' => $fill];
    }
}
