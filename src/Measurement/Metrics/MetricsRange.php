<?php

declare(strict_types=1);

namespace NeneServe\Measurement\Metrics;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Parses the `from`/`to` query window for metrics. Both default to a 30-day
 * window ending today (UTC). Returns null on malformed input.
 */
final class MetricsRange
{
    /**
     * @return array{0: string, 1: string}|null [fromDate, toDate]
     */
    public static function fromRequest(ServerRequestInterface $request): ?array
    {
        $params = $request->getQueryParams();

        $to = isset($params['to']) && is_string($params['to']) ? $params['to'] : gmdate('Y-m-d');
        $from = isset($params['from']) && is_string($params['from'])
            ? $params['from']
            : gmdate('Y-m-d', strtotime('-29 days'));

        if (!self::isDate($from) || !self::isDate($to)) {
            return null;
        }

        return [$from, $to];
    }

    private static function isDate(string $value): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1;
    }
}
