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

    /** Append a conversion attributed to delivery (ADR 0009; not a Contact submission). */
    public function recordConversion(ConversionEvent $event): void;

    /**
     * Daily conversion counts per date/placement over an inclusive range.
     *
     * @return list<array{date: string, placement_id: string, conversions: int}>
     */
    public function dailyConversions(string $organizationId, string $fromDate, string $toDate): array;

    /**
     * Records a serve request (for fill rate). `filled` is true when a
     * non-fallback creative was returned, false for an empty/capped serve.
     */
    public function recordServeRequest(string $organizationId, string $placementId, bool $filled): void;

    /**
     * Daily fill rate per date/placement over an inclusive [from, to] range.
     *
     * @return list<FillRow>
     */
    public function dailyFillRates(string $organizationId, string $fromDate, string $toDate): array;

    /**
     * Daily metrics for one tenant over an inclusive [from, to] date range
     * (UTC `Y-m-d`), grouped by date/placement/creative.
     *
     * @return list<MetricsRow>
     */
    public function dailyMetrics(string $organizationId, string $fromDate, string $toDate): array;

    /**
     * Billable impression/click counts for a set of creatives (spend derivation,
     * billing §3.1/§3.4). Counts every recorded event — erased rows keep their
     * count (privacy erasure forgets the link, never the figure).
     *
     * @param list<string> $creativeIds
     * @return array{impressions: int, clicks: int}
     */
    public function billableCountsForCreatives(string $organizationId, array $creativeIds): array;

    /**
     * Sensitive per-visitor breakdown (visitor_bucket level) for an admin tool
     * with `include_sensitive` (measurement-spec MCP/AI). Excludes erased rows
     * and rows without a bucket. The caller MUST gate + audit this access.
     *
     * @return list<array{date: string, placement_id: string, creative_id: string, visitor_bucket: string, impressions: int}>
     */
    public function visitorBreakdown(string $organizationId, string $fromDate, string $toDate): array;

    /**
     * Data-subject access (privacy §5): non-erased impression records for one
     * hashed visitor bucket in a tenant.
     *
     * @return list<array{type: string, date: string, placement_id: string, creative_id: string}>
     */
    public function exportVisitorData(string $organizationId, string $visitorBucket): array;

    /**
     * Governed retention purge (billing §7, privacy §6, ADR 0022 §7). Physically
     * removes events past retention: ordinary measurement older than
     * `ordinaryBefore`, and billing-relevant events (creative in
     * `$billingCreativeIds`) only once older than `billingBefore` (statutory).
     * Run by the privileged role; spend snapshots and audit are never touched.
     *
     * @param list<string> $billingCreativeIds
     * @return int rows purged
     */
    public function purgeExpiredEvents(string $organizationId, string $ordinaryBefore, string $billingBefore, array $billingCreativeIds): int;

    /**
     * Data-subject erasure (privacy §5): an **additive tombstone** — sets
     * `erased_at` and forgets the visitor link, but never edits the counts (the
     * impression rows remain for aggregation). Returns the number tombstoned.
     */
    public function eraseVisitor(string $organizationId, string $visitorBucket): int;
}
