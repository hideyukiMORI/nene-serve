<?php

declare(strict_types=1);

namespace NeneServe\Measurement;

/**
 * Pure daily fill-rate aggregation shared by the in-memory and file event
 * stores. Groups serve requests by date/placement within a tenant + date range.
 */
final class FillRateAggregator
{
    /**
     * @param list<array{org: string, date: string, placement: string, filled: bool}> $serves
     * @return list<FillRow>
     */
    public static function aggregate(array $serves, string $organizationId, string $fromDate, string $toDate): array
    {
        /** @var array<string, array{date: string, placement: string, requests: int, fills: int}> $buckets */
        $buckets = [];
        foreach ($serves as $row) {
            if ($row['org'] !== $organizationId || $row['date'] < $fromDate || $row['date'] > $toDate) {
                continue;
            }
            $key = $row['date'] . '|' . $row['placement'];
            $buckets[$key] ??= ['date' => $row['date'], 'placement' => $row['placement'], 'requests' => 0, 'fills' => 0];
            ++$buckets[$key]['requests'];
            if ($row['filled']) {
                ++$buckets[$key]['fills'];
            }
        }
        ksort($buckets);

        return array_values(array_map(
            static fn (array $b): FillRow => new FillRow($b['date'], $b['placement'], $b['requests'], $b['fills']),
            $buckets,
        ));
    }
}
