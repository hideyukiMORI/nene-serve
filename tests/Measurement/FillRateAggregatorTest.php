<?php

declare(strict_types=1);

namespace NeneServe\Tests\Measurement;

use NeneServe\Measurement\FillRateAggregator;
use NeneServe\Measurement\FillRow;
use PHPUnit\Framework\TestCase;

/**
 * Daily fill-rate aggregation. Tenant isolation and the inclusive date-range
 * window are the load-bearing filters, so both edges of the window and a
 * foreign tenant are checked explicitly.
 */
final class FillRateAggregatorTest extends TestCase
{
    /**
     * @param array{org?: string, date?: string, placement?: string, filled?: bool} $o
     *
     * @return array{org: string, date: string, placement: string, filled: bool}
     */
    private function serve(array $o): array
    {
        return $o + ['org' => 'org-1', 'date' => '2026-06-06', 'placement' => 'plc-1', 'filled' => true];
    }

    public function testEmptyInputYieldsNoRows(): void
    {
        self::assertSame([], FillRateAggregator::aggregate([], 'org-1', '2026-06-01', '2026-06-30'));
    }

    public function testCountsRequestsAndFillsPerDatePlacement(): void
    {
        $rows = FillRateAggregator::aggregate([
            $this->serve(['filled' => true]),
            $this->serve(['filled' => false]),
            $this->serve(['filled' => true]),
        ], 'org-1', '2026-06-01', '2026-06-30');

        self::assertCount(1, $rows);
        self::assertSame(3, $rows[0]->serveRequests);
        self::assertSame(2, $rows[0]->fills);
    }

    public function testExcludesOtherTenants(): void
    {
        $rows = FillRateAggregator::aggregate([
            $this->serve(['org' => 'org-1']),
            $this->serve(['org' => 'org-2']),
        ], 'org-1', '2026-06-01', '2026-06-30');

        self::assertCount(1, $rows);
        self::assertSame(1, $rows[0]->serveRequests);
    }

    public function testIncludesBothWindowEdgesAndExcludesOutside(): void
    {
        $rows = FillRateAggregator::aggregate([
            $this->serve(['date' => '2026-05-31']), // day before window — excluded
            $this->serve(['date' => '2026-06-01']), // lower edge — included
            $this->serve(['date' => '2026-06-30']), // upper edge — included
            $this->serve(['date' => '2026-07-01']), // day after window — excluded
        ], 'org-1', '2026-06-01', '2026-06-30');

        $dates = array_map(static fn (FillRow $r): string => $r->date, $rows);
        self::assertSame(['2026-06-01', '2026-06-30'], $dates);
    }

    public function testGroupsByDateThenPlacementAndSorts(): void
    {
        $rows = FillRateAggregator::aggregate([
            $this->serve(['date' => '2026-06-07', 'placement' => 'plc-2']),
            $this->serve(['date' => '2026-06-06', 'placement' => 'plc-1']),
            $this->serve(['date' => '2026-06-06', 'placement' => 'plc-2']),
        ], 'org-1', '2026-06-01', '2026-06-30');

        $keys = array_map(static fn (FillRow $r): string => $r->date . '|' . $r->placementId, $rows);
        self::assertSame(['2026-06-06|plc-1', '2026-06-06|plc-2', '2026-06-07|plc-2'], $keys);
    }

    /** @return iterable<string, array{int, int, float}> */
    public static function fillRates(): iterable
    {
        yield 'zero requests is zero, no divide-by-zero' => [0, 0, 0.0];
        yield 'no fills' => [10, 0, 0.0];
        yield 'half' => [10, 5, 0.5];
        yield 'full' => [10, 10, 1.0];
        yield 'one third' => [3, 1, 1 / 3];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('fillRates')]
    public function testFillRowRate(int $requests, int $fills, float $expected): void
    {
        self::assertSame($expected, (new FillRow('2026-06-06', 'plc-1', $requests, $fills))->fillRate());
    }
}
