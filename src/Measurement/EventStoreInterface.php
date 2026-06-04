<?php

declare(strict_types=1);

namespace NeneServe\Measurement;

/**
 * Append-only impression/click event sink + daily aggregation (measurement-spec).
 * Reporting and billing read the **same** records (measurement-spec §billable).
 */
interface EventStoreInterface
{
    public function recordImpression(ImpressionEvent $event): void;

    public function recordClick(ClickEvent $event): void;

    /**
     * Daily metrics for one tenant over an inclusive [from, to] date range
     * (UTC `Y-m-d`), grouped by date/placement/creative.
     *
     * @return list<MetricsRow>
     */
    public function dailyMetrics(string $organizationId, string $fromDate, string $toDate): array;

    /**
     * Data-subject access (privacy §5): non-erased impression records for one
     * hashed visitor bucket in a tenant.
     *
     * @return list<array{type: string, date: string, placement_id: string, creative_id: string}>
     */
    public function exportVisitorData(string $organizationId, string $visitorBucket): array;

    /**
     * Data-subject erasure (privacy §5): an **additive tombstone** — sets
     * `erased_at` and forgets the visitor link, but never edits the counts (the
     * impression rows remain for aggregation). Returns the number tombstoned.
     */
    public function eraseVisitor(string $organizationId, string $visitorBucket): int;
}
