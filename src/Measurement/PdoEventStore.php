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
                (id, organization_id, placement_id, creative_id, occurred_at, country_code, placement_page_url, visitor_bucket, non_billable_reason, consent_state)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
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
            $event->consentState,
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

    public function recordServeRequest(string $organizationId, string $placementId, bool $filled): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO serve_requests (id, organization_id, placement_id, occurred_at, filled)
             VALUES (?, ?, ?, ?, ?)',
        );
        $stmt->execute([bin2hex(random_bytes(16)), $organizationId, $placementId, gmdate('Y-m-d H:i:s'), $filled ? 1 : 0]);
    }

    public function dailyFillRates(string $organizationId, string $fromDate, string $toDate): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT DATE(occurred_at) AS date, placement_id,
                    COUNT(*) AS serve_requests, SUM(filled) AS fills
             FROM serve_requests
             WHERE organization_id = ? AND DATE(occurred_at) BETWEEN ? AND ?
             GROUP BY date, placement_id
             ORDER BY date, placement_id',
        );
        $stmt->execute([$organizationId, $fromDate, $toDate]);

        return array_map(
            static fn (array $row): FillRow => new FillRow(
                (string) $row['date'],
                (string) $row['placement_id'],
                (int) $row['serve_requests'],
                (int) $row['fills'],
            ),
            array_values($stmt->fetchAll()),
        );
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

    public function billableCountsForCreatives(string $organizationId, array $creativeIds): array
    {
        if ($creativeIds === []) {
            return ['impressions' => 0, 'clicks' => 0];
        }
        $in = implode(',', array_fill(0, count($creativeIds), '?'));
        $params = array_merge([$organizationId], $creativeIds);

        $impStmt = $this->pdo->prepare("SELECT COUNT(*) FROM impressions WHERE organization_id = ? AND creative_id IN ($in)");
        $impStmt->execute($params);
        $clkStmt = $this->pdo->prepare("SELECT COUNT(*) FROM clicks WHERE organization_id = ? AND creative_id IN ($in)");
        $clkStmt->execute($params);

        return ['impressions' => (int) $impStmt->fetchColumn(), 'clicks' => (int) $clkStmt->fetchColumn()];
    }

    public function visitorBreakdown(string $organizationId, string $fromDate, string $toDate): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT DATE(occurred_at) AS date, placement_id, creative_id, visitor_bucket,
                    COUNT(*) AS impressions
             FROM impressions
             WHERE organization_id = ? AND visitor_bucket IS NOT NULL AND erased_at IS NULL
               AND DATE(occurred_at) BETWEEN ? AND ?
             GROUP BY date, placement_id, creative_id, visitor_bucket
             ORDER BY date, placement_id, creative_id, visitor_bucket',
        );
        $stmt->execute([$organizationId, $fromDate, $toDate]);

        return array_map(
            static fn (array $row): array => [
                'date' => (string) $row['date'],
                'placement_id' => (string) $row['placement_id'],
                'creative_id' => (string) $row['creative_id'],
                'visitor_bucket' => (string) $row['visitor_bucket'],
                'impressions' => (int) $row['impressions'],
            ],
            array_values($stmt->fetchAll()),
        );
    }

    public function exportVisitorData(string $organizationId, string $visitorBucket): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT DATE(occurred_at) AS date, placement_id, creative_id
             FROM impressions
             WHERE organization_id = ? AND visitor_bucket = ? AND erased_at IS NULL
             ORDER BY occurred_at',
        );
        $stmt->execute([$organizationId, $visitorBucket]);

        return array_map(
            static fn (array $row): array => [
                'type' => 'impression',
                'date' => (string) $row['date'],
                'placement_id' => (string) $row['placement_id'],
                'creative_id' => (string) $row['creative_id'],
            ],
            array_values($stmt->fetchAll()),
        );
    }

    public function eraseVisitor(string $organizationId, string $visitorBucket): int
    {
        // Additive tombstone: stamp erased_at and forget the visitor link; the
        // rows (and therefore the counts) are never deleted (privacy §5).
        $stmt = $this->pdo->prepare(
            'UPDATE impressions
             SET erased_at = UTC_TIMESTAMP(), visitor_bucket = NULL
             WHERE organization_id = ? AND visitor_bucket = ? AND erased_at IS NULL',
        );
        $stmt->execute([$organizationId, $visitorBucket]);

        return $stmt->rowCount();
    }
}
