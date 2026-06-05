<?php

declare(strict_types=1);

namespace NeneServe\Measurement\Metrics;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Measurement\EventStoreInterface;

/**
 * JSON daily time-series for one tenant (measurement-spec reporting): per
 * date/placement/creative impressions, clicks, CTR; plus per date/placement
 * fill rate. Aggregated only — no visitor identifiers (privacy N8).
 *
 * {@see self::sensitiveReport()} adds the per-`visitor_bucket` breakdown for an
 * admin with `include_sensitive`; that access is **audited** (ADR 0022 §4) —
 * ordinary aggregate reads are not.
 */
final readonly class GetMetricsUseCase
{
    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private EventStoreInterface $events,
        private DatabaseTransactionManagerInterface $transactions,
        private RequestScopedHolder $organizationId,
    ) {
    }

    /**
     * @return array{from: string, to: string, rows: list<array<string, mixed>>, fill: list<array<string, mixed>>, conversions: list<array<string, mixed>>}
     */
    public function report(string $fromDate, string $toDate): array
    {
        $organizationId = $this->organizationId->get();

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

        return [
            'from' => $fromDate,
            'to' => $toDate,
            'rows' => $rows,
            'fill' => $fill,
            'conversions' => $this->events->dailyConversions($organizationId, $fromDate, $toDate),
        ];
    }

    /**
     * Aggregate report plus the sensitive per-visitor_bucket breakdown. Records a
     * `metrics.read_sensitive` audit event (atomic). Caller must have already
     * checked capability.
     *
     * @return array{from: string, to: string, rows: list<array<string, mixed>>, fill: list<array<string, mixed>>, conversions: list<array<string, mixed>>, sensitive: list<array<string, mixed>>}
     */
    public function sensitiveReport(string $actorUserId, string $fromDate, string $toDate): array
    {
        $organizationId = $this->organizationId->get();
        $report = $this->report($fromDate, $toDate);
        $sensitive = $this->events->visitorBreakdown($organizationId, $fromDate, $toDate);

        $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($organizationId, $actorUserId, $fromDate, $toDate, $sensitive): void {
                (new PdoAuditLog($tx))->record(
                    $organizationId,
                    $actorUserId,
                    'metrics.read_sensitive',
                    'metrics',
                    $fromDate . '..' . $toDate,
                    ['from' => $fromDate, 'to' => $toDate, 'visitor_rows' => count($sensitive)],
                );
            },
        );

        return $report + ['sensitive' => $sensitive];
    }
}
