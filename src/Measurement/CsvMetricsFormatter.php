<?php

declare(strict_types=1);

namespace NeneServe\Measurement;

/**
 * Formats daily metrics as CSV. Columns are the documented `ExportMetrics`
 * schema (measurement-spec): date, placement_id, creative_id, impressions,
 * clicks, ctr. Aggregated rows only — no visitor identifiers.
 */
final class CsvMetricsFormatter
{
    public const COLUMNS = ['date', 'placement_id', 'creative_id', 'impressions', 'clicks', 'ctr'];

    /**
     * @param list<MetricsRow> $rows
     */
    public static function format(array $rows): string
    {
        $out = fopen('php://temp', 'r+');
        if ($out === false) {
            return '';
        }

        fputcsv($out, self::COLUMNS, escape: '');
        foreach ($rows as $row) {
            fputcsv($out, [
                $row->date,
                $row->placementId,
                $row->creativeId,
                (string) $row->impressions,
                (string) $row->clicks,
                number_format($row->ctr(), 4, '.', ''),
            ], escape: '');
        }

        rewind($out);
        $csv = (string) stream_get_contents($out);
        fclose($out);

        return $csv;
    }
}
