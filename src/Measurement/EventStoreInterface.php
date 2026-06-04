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
}
