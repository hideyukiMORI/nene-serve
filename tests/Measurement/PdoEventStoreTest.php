<?php

declare(strict_types=1);

namespace NeneServe\Tests\Measurement;

use NeneServe\Measurement\MetricsRow;
use NeneServe\Measurement\PdoEventStore;
use NeneServe\Tests\Support\TestDatabase;
use PHPUnit\Framework\TestCase;

final class PdoEventStoreTest extends TestCase
{
    public function testDailyMetricsAggregatesImpressionsAndClicksPerDayScopedByOrgAndRange(): void
    {
        $query = TestDatabase::withSchema('impressions', 'clicks');

        // org-1: two impressions + one click on p1/c1 (2026-06-01), one impression
        // on the next day, plus a click outside the range and another org's row.
        TestDatabase::seed($query, 'impressions', $this->impression('i1', 'org-1', 'p1', 'c1', '2026-06-01 09:00:00'));
        TestDatabase::seed($query, 'impressions', $this->impression('i2', 'org-1', 'p1', 'c1', '2026-06-01 21:30:00'));
        TestDatabase::seed($query, 'impressions', $this->impression('i3', 'org-1', 'p1', 'c1', '2026-06-02 08:00:00'));
        TestDatabase::seed($query, 'impressions', $this->impression('i4', 'org-2', 'p9', 'c9', '2026-06-01 10:00:00'));
        TestDatabase::seed($query, 'clicks', $this->click('k1', 'org-1', 'p1', 'c1', '2026-06-01 12:00:00'));
        TestDatabase::seed($query, 'clicks', $this->click('k2', 'org-1', 'p1', 'c1', '2026-06-09 12:00:00'));

        $rows = (new PdoEventStore($query))->dailyMetrics('org-1', '2026-06-01', '2026-06-02');

        self::assertCount(2, $rows);

        $first = $rows[0];
        self::assertInstanceOf(MetricsRow::class, $first);
        self::assertSame('2026-06-01', $first->date);
        self::assertSame('p1', $first->placementId);
        self::assertSame('c1', $first->creativeId);
        self::assertSame(2, $first->impressions);
        self::assertSame(1, $first->clicks);

        $second = $rows[1];
        self::assertSame('2026-06-02', $second->date);
        self::assertSame(1, $second->impressions);
        self::assertSame(0, $second->clicks);
    }

    public function testDailyMetricsIsEmptyWhenNoEventsInRange(): void
    {
        $query = TestDatabase::withSchema('impressions', 'clicks');

        self::assertSame([], (new PdoEventStore($query))->dailyMetrics('org-1', '2026-06-01', '2026-06-30'));
    }

    /** @return array<string, scalar|null> */
    private function impression(string $id, string $org, string $placement, string $creative, string $at): array
    {
        return [
            'id' => $id,
            'organization_id' => $org,
            'placement_id' => $placement,
            'creative_id' => $creative,
            'occurred_at' => $at,
        ];
    }

    /** @return array<string, scalar|null> */
    private function click(string $id, string $org, string $placement, string $creative, string $at): array
    {
        return [
            'id' => $id,
            'organization_id' => $org,
            'placement_id' => $placement,
            'creative_id' => $creative,
            'occurred_at' => $at,
        ];
    }
}
