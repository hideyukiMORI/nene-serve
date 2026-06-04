<?php

declare(strict_types=1);

namespace NeneServe\Tests\Measurement;

use NeneServe\Measurement\ClickEvent;
use NeneServe\Measurement\CsvMetricsFormatter;
use NeneServe\Measurement\ImpressionEvent;
use NeneServe\Measurement\InMemoryEventStore;
use PHPUnit\Framework\TestCase;

final class MetricsAggregatorTest extends TestCase
{
    public function testDailyAggregationGroupsByDatePlacementCreative(): void
    {
        $store = new InMemoryEventStore();
        $store->recordImpression($this->impression('org-1', 'p1', 'c1', '2026-06-01T10:00:00+00:00'));
        $store->recordImpression($this->impression('org-1', 'p1', 'c1', '2026-06-01T11:00:00+00:00'));
        $store->recordClick($this->click('org-1', 'p1', 'c1', '2026-06-01T12:00:00+00:00'));

        $rows = $store->dailyMetrics('org-1', '2026-06-01', '2026-06-30');
        self::assertCount(1, $rows);
        self::assertSame(2, $rows[0]->impressions);
        self::assertSame(1, $rows[0]->clicks);
        self::assertSame(0.5, $rows[0]->ctr());
    }

    public function testDateRangeAndTenantIsolation(): void
    {
        $store = new InMemoryEventStore();
        $store->recordImpression($this->impression('org-1', 'p1', 'c1', '2026-06-01T10:00:00+00:00'));
        $store->recordImpression($this->impression('org-1', 'p1', 'c1', '2026-05-01T10:00:00+00:00')); // out of range
        $store->recordImpression($this->impression('org-2', 'p9', 'c9', '2026-06-01T10:00:00+00:00')); // other tenant

        $rows = $store->dailyMetrics('org-1', '2026-06-01', '2026-06-30');
        self::assertCount(1, $rows);
        self::assertSame('p1', $rows[0]->placementId);
    }

    public function testCsvFormat(): void
    {
        $store = new InMemoryEventStore();
        $store->recordImpression($this->impression('org-1', 'p1', 'c1', '2026-06-01T10:00:00+00:00'));
        $store->recordClick($this->click('org-1', 'p1', 'c1', '2026-06-01T12:00:00+00:00'));

        $csv = CsvMetricsFormatter::format($store->dailyMetrics('org-1', '2026-06-01', '2026-06-30'));
        $lines = array_values(array_filter(explode("\n", trim($csv))));

        self::assertSame('date,placement_id,creative_id,impressions,clicks,ctr', $lines[0]);
        self::assertSame('2026-06-01,p1,c1,1,1,1.0000', $lines[1]);
    }

    private function impression(string $org, string $p, string $c, string $at): ImpressionEvent
    {
        return new ImpressionEvent('imp-' . bin2hex(random_bytes(4)), $org, $p, $c, $at);
    }

    private function click(string $org, string $p, string $c, string $at): ClickEvent
    {
        return new ClickEvent('clk-' . bin2hex(random_bytes(4)), $org, $p, $c, $at);
    }
}
