<?php

declare(strict_types=1);

namespace NeneServe\Measurement;

use PDO;

/**
 * Production event store. Append-only inserts; aggregation via GROUP BY on the
 * UTC date. Reporting and billing read the same rows (measurement-spec).
 */
final class PdoEventStore implements EventStoreInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function recordImpression(ImpressionEvent $event): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO impressions
                (id, organization_id, placement_id, creative_id, occurred_at, country_code, placement_page_url, visitor_bucket, non_billable_reason)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        );
        $stmt->execute([
            $event->impressionId,
            $event->organizationId,
            $event->placementId,
            $event->creativeId,
            $event->occurredAt,
            $event->countryCode,
            $event->placementPageUrl,
            $event->visitorBucket,
            $event->nonBillableReason,
        ]);
    }

    public function recordClick(ClickEvent $event): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO clicks
                (id, organization_id, placement_id, creative_id, occurred_at, country_code, non_billable_reason)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
        );
        $stmt->execute([
            $event->clickId,
            $event->organizationId,
            $event->placementId,
            $event->creativeId,
            $event->occurredAt,
            $event->countryCode,
            $event->nonBillableReason,
        ]);
    }

    public function dailyMetrics(string $organizationId, string $fromDate, string $toDate): array
    {
        $sql =
            "SELECT d AS date, placement_id, creative_id, SUM(imp) AS impressions, SUM(clk) AS clicks FROM (
                SELECT DATE(occurred_at) d, placement_id, creative_id, 1 imp, 0 clk
                FROM impressions WHERE organization_id = :org AND DATE(occurred_at) BETWEEN :from AND :to
                UNION ALL
                SELECT DATE(occurred_at) d, placement_id, creative_id, 0 imp, 1 clk
                FROM clicks WHERE organization_id = :org AND DATE(occurred_at) BETWEEN :from AND :to
            ) e
            GROUP BY d, placement_id, creative_id
            ORDER BY d, placement_id, creative_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['org' => $organizationId, 'from' => $fromDate, 'to' => $toDate]);

        return array_map(
            static fn (array $row): MetricsRow => new MetricsRow(
                (string) $row['date'],
                (string) $row['placement_id'],
                (string) $row['creative_id'],
                (int) $row['impressions'],
                (int) $row['clicks'],
            ),
            array_values($stmt->fetchAll()),
        );
    }
}
