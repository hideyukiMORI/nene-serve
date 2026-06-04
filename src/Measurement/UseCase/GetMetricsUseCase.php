<?php

declare(strict_types=1);

namespace NeneServe\Measurement\UseCase;

use NeneServe\Audit\AuditLogInterface;
use NeneServe\Measurement\EventStoreInterface;
use NeneServe\Support\TransactionManagerInterface;
use NeneServe\Tenant\AuthContext;

/**
 * JSON daily time-series for one tenant (measurement-spec reporting): per
 * date/placement/creative impressions, clicks, CTR; plus per date/placement
 * fill rate. Aggregated only — no visitor identifiers (privacy N8).
 *
 * {@see self::sensitiveReport()} additionally returns the per-`visitor_bucket`
 * breakdown for an admin with `include_sensitive`; that access is **audited**
 * (ADR 0022 §4) — ordinary aggregate reads are not.
 */
final class GetMetricsUseCase
{
    public function __construct(
        private readonly EventStoreInterface $events,
        private readonly AuditLogInterface $audit,
        private readonly TransactionManagerInterface $tx,
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

    /**
     * Aggregate report plus the sensitive per-visitor_bucket breakdown. Records a
     * `metrics.read_sensitive` audit event (atomic) — sensitive access is logged
     * (measurement-spec, ADR 0022 §4). Caller must have already checked capability.
     *
     * @return array{from: string, to: string, rows: list<array<string, mixed>>, fill: list<array<string, mixed>>, sensitive: list<array<string, mixed>>}
     */
    public function sensitiveReport(AuthContext $actor, string $fromDate, string $toDate): array
    {
        $report = $this->report($actor->organizationId, $fromDate, $toDate);
        $sensitive = $this->events->visitorBreakdown($actor->organizationId, $fromDate, $toDate);

        $this->tx->transactional(function () use ($actor, $fromDate, $toDate, $sensitive): void {
            $this->audit->record(
                $actor->organizationId,
                $actor->userId,
                'metrics.read_sensitive',
                'metrics',
                $fromDate . '..' . $toDate,
                ['from' => $fromDate, 'to' => $toDate, 'visitor_rows' => count($sensitive)],
            );
        });

        return $report + ['sensitive' => $sensitive];
    }
}
