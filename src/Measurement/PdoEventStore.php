<?php

declare(strict_types=1);

namespace NeneServe\Measurement;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeneServe\Support\Id;
use NeneServe\Support\SqlDialect;

/**
 * Production event store on the NENE2 query executor. Append-only inserts;
 * aggregation via GROUP BY on the UTC date. Reporting and billing read the same
 * rows (measurement-spec). Date truncation is dialect-aware ({@see SqlDialect}).
 */
final readonly class PdoEventStore implements EventStoreInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private SqlDialect $dialect = SqlDialect::Mysql,
    ) {
    }

    public function recordImpression(ImpressionEvent $event): void
    {
        $this->query->execute(
            'INSERT INTO impressions
                (id, organization_id, placement_id, creative_id, occurred_at, country_code, placement_page_url, visitor_bucket, non_billable_reason, consent_state)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
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
            ],
        );
    }

    public function recordClick(ClickEvent $event): void
    {
        $this->query->execute(
            'INSERT INTO clicks
                (id, organization_id, placement_id, creative_id, occurred_at, country_code, non_billable_reason)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $event->clickId,
                $event->organizationId,
                $event->placementId,
                $event->creativeId,
                $event->occurredAt,
                $event->countryCode,
                $event->nonBillableReason,
            ],
        );
    }

    public function recordConversion(ConversionEvent $event): void
    {
        $this->query->execute(
            'INSERT INTO conversions (id, organization_id, placement_id, creative_id, occurred_at, country_code)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $event->conversionId,
                $event->organizationId,
                $event->placementId,
                $event->creativeId,
                $event->occurredAt,
                $event->countryCode,
            ],
        );
    }

    public function dailyConversions(string $organizationId, string $fromDate, string $toDate): array
    {
        $dateExpr = $this->dialect->dateExpr('occurred_at');
        $rows = $this->query->fetchAll(
            "SELECT {$dateExpr} AS date, placement_id, COUNT(*) AS conversions
             FROM conversions
             WHERE organization_id = ? AND {$dateExpr} BETWEEN ? AND ?
             GROUP BY date, placement_id
             ORDER BY date, placement_id",
            [$organizationId, $fromDate, $toDate],
        );

        return array_map(
            static fn (array $row): array => [
                'date' => (string) $row['date'],
                'placement_id' => (string) $row['placement_id'],
                'conversions' => (int) $row['conversions'],
            ],
            $rows,
        );
    }

    public function recordServeRequest(string $organizationId, string $placementId, bool $filled): void
    {
        $this->query->execute(
            'INSERT INTO serve_requests (id, organization_id, placement_id, occurred_at, filled)
             VALUES (?, ?, ?, ?, ?)',
            [Id::random(16), $organizationId, $placementId, gmdate('Y-m-d H:i:s'), $filled ? 1 : 0],
        );
    }

    public function dailyFillRates(string $organizationId, string $fromDate, string $toDate): array
    {
        $dateExpr = $this->dialect->dateExpr('occurred_at');
        $rows = $this->query->fetchAll(
            "SELECT {$dateExpr} AS date, placement_id,
                    COUNT(*) AS serve_requests, SUM(filled) AS fills
             FROM serve_requests
             WHERE organization_id = ? AND {$dateExpr} BETWEEN ? AND ?
             GROUP BY date, placement_id
             ORDER BY date, placement_id",
            [$organizationId, $fromDate, $toDate],
        );

        return array_map(
            static fn (array $row): FillRow => new FillRow(
                (string) $row['date'],
                (string) $row['placement_id'],
                (int) $row['serve_requests'],
                (int) $row['fills'],
            ),
            $rows,
        );
    }

    public function dailyMetrics(string $organizationId, string $fromDate, string $toDate): array
    {
        // Each subquery binds its own placeholders: native prepared statements
        // (emulation off) reject a named parameter reused across the UNION.
        $dateExpr = $this->dialect->dateExpr('occurred_at');
        $rows = $this->query->fetchAll(
            "SELECT d AS date, placement_id, creative_id, SUM(imp) AS impressions, SUM(clk) AS clicks FROM (
                SELECT {$dateExpr} d, placement_id, creative_id, 1 imp, 0 clk
                FROM impressions WHERE organization_id = :org_i AND {$dateExpr} BETWEEN :from_i AND :to_i
                UNION ALL
                SELECT {$dateExpr} d, placement_id, creative_id, 0 imp, 1 clk
                FROM clicks WHERE organization_id = :org_c AND {$dateExpr} BETWEEN :from_c AND :to_c
            ) e
            GROUP BY d, placement_id, creative_id
            ORDER BY d, placement_id, creative_id",
            [
                'org_i' => $organizationId, 'from_i' => $fromDate, 'to_i' => $toDate,
                'org_c' => $organizationId, 'from_c' => $fromDate, 'to_c' => $toDate,
            ],
        );

        return array_map(
            static fn (array $row): MetricsRow => new MetricsRow(
                (string) $row['date'],
                (string) $row['placement_id'],
                (string) $row['creative_id'],
                (int) $row['impressions'],
                (int) $row['clicks'],
            ),
            $rows,
        );
    }

    public function purgeExpiredEvents(string $organizationId, string $ordinaryBefore, string $billingBefore, array $billingCreativeIds): int
    {
        return $this->purgeTable('impressions', $organizationId, $ordinaryBefore, $billingBefore, $billingCreativeIds)
            + $this->purgeTable('clicks', $organizationId, $ordinaryBefore, $billingBefore, $billingCreativeIds);
    }

    /** @param list<string> $billingCreativeIds */
    private function purgeTable(string $table, string $organizationId, string $ordinaryBefore, string $billingBefore, array $billingCreativeIds): int
    {
        if ($billingCreativeIds === []) {
            // No billing-relevant creatives: everything older than the ordinary cutoff goes.
            return $this->query->execute(
                "DELETE FROM {$table} WHERE organization_id = ? AND DATE(occurred_at) < ?",
                [$organizationId, $ordinaryBefore],
            );
        }

        $in = implode(',', array_fill(0, count($billingCreativeIds), '?'));
        $sql = "DELETE FROM {$table} WHERE organization_id = ? AND (
                    (creative_id NOT IN ($in) AND DATE(occurred_at) < ?)
                 OR (creative_id IN ($in) AND DATE(occurred_at) < ?)
                )";

        return $this->query->execute(
            $sql,
            array_merge([$organizationId], $billingCreativeIds, [$ordinaryBefore], $billingCreativeIds, [$billingBefore]),
        );
    }

    public function billableCountsForCreatives(string $organizationId, array $creativeIds): array
    {
        if ($creativeIds === []) {
            return ['impressions' => 0, 'clicks' => 0];
        }
        $in = implode(',', array_fill(0, count($creativeIds), '?'));
        $params = array_merge([$organizationId], $creativeIds);

        $impRow = $this->query->fetchOne("SELECT COUNT(*) AS cnt FROM impressions WHERE organization_id = ? AND creative_id IN ($in)", $params);
        $clkRow = $this->query->fetchOne("SELECT COUNT(*) AS cnt FROM clicks WHERE organization_id = ? AND creative_id IN ($in)", $params);

        return [
            'impressions' => (int) ($impRow['cnt'] ?? 0),
            'clicks' => (int) ($clkRow['cnt'] ?? 0),
        ];
    }

    public function visitorBreakdown(string $organizationId, string $fromDate, string $toDate): array
    {
        $rows = $this->query->fetchAll(
            'SELECT DATE(occurred_at) AS date, placement_id, creative_id, visitor_bucket,
                    COUNT(*) AS impressions
             FROM impressions
             WHERE organization_id = ? AND visitor_bucket IS NOT NULL AND erased_at IS NULL
               AND DATE(occurred_at) BETWEEN ? AND ?
             GROUP BY date, placement_id, creative_id, visitor_bucket
             ORDER BY date, placement_id, creative_id, visitor_bucket',
            [$organizationId, $fromDate, $toDate],
        );

        return array_map(
            static fn (array $row): array => [
                'date' => (string) $row['date'],
                'placement_id' => (string) $row['placement_id'],
                'creative_id' => (string) $row['creative_id'],
                'visitor_bucket' => (string) $row['visitor_bucket'],
                'impressions' => (int) $row['impressions'],
            ],
            $rows,
        );
    }

    public function exportVisitorData(string $organizationId, string $visitorBucket): array
    {
        $rows = $this->query->fetchAll(
            'SELECT DATE(occurred_at) AS date, placement_id, creative_id
             FROM impressions
             WHERE organization_id = ? AND visitor_bucket = ? AND erased_at IS NULL
             ORDER BY occurred_at',
            [$organizationId, $visitorBucket],
        );

        return array_map(
            static fn (array $row): array => [
                'type' => 'impression',
                'date' => (string) $row['date'],
                'placement_id' => (string) $row['placement_id'],
                'creative_id' => (string) $row['creative_id'],
            ],
            $rows,
        );
    }

    public function eraseVisitor(string $organizationId, string $visitorBucket): int
    {
        // Additive tombstone: stamp erased_at and forget the visitor link; the
        // rows (and therefore the counts) are never deleted (privacy §5).
        return $this->query->execute(
            'UPDATE impressions
             SET erased_at = UTC_TIMESTAMP(), visitor_bucket = NULL
             WHERE organization_id = ? AND visitor_bucket = ? AND erased_at IS NULL',
            [$organizationId, $visitorBucket],
        );
    }
}
