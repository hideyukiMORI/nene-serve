<?php

declare(strict_types=1);

namespace NeneServe\Measurement;

/**
 * Pure daily aggregation shared by the in-memory and file event stores. Groups
 * by date/placement/creative within a tenant and an inclusive date range.
 */
final class MetricsAggregator
{
    /**
     * @param list<array{org: string, date: string, placement: string, creative: string}> $impressions
     * @param list<array{org: string, date: string, placement: string, creative: string}> $clicks
     * @return list<MetricsRow>
     */
    public static function aggregate(array $impressions, array $clicks, string $organizationId, string $fromDate, string $toDate): array
    {
        /** @var array<string, array{date: string, placement: string, creative: string, imp: int, clk: int}> $buckets */
        $buckets = [];

        $fold = static function (array $rows, string $metric) use (&$buckets, $organizationId, $fromDate, $toDate): void {
            foreach ($rows as $row) {
                if ($row['org'] !== $organizationId || $row['date'] < $fromDate || $row['date'] > $toDate) {
                    continue;
                }
                $key = $row['date'] . '|' . $row['placement'] . '|' . $row['creative'];
                $buckets[$key] ??= [
                    'date' => $row['date'],
                    'placement' => $row['placement'],
                    'creative' => $row['creative'],
                    'imp' => 0,
                    'clk' => 0,
                ];
                $buckets[$key][$metric === 'imp' ? 'imp' : 'clk']++;
            }
        };

        $fold($impressions, 'imp');
        $fold($clicks, 'clk');

        ksort($buckets);

        return array_values(array_map(
            static fn (array $b): MetricsRow => new MetricsRow($b['date'], $b['placement'], $b['creative'], $b['imp'], $b['clk']),
            $buckets,
        ));
    }
}
