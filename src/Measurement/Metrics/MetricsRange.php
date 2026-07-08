<?php

declare(strict_types=1);

namespace NeneServe\Measurement\Metrics;

use Nene2\Http\ClockInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Parses the `from`/`to` query window for metrics. Both default to a 30-day
 * window ending today (UTC), where "today" comes from the caller's injected
 * clock. Returns null on malformed input.
 */
final class MetricsRange
{
    /**
     * @return array{0: string, 1: string}|null [fromDate, toDate]
     */
    public static function fromRequest(ServerRequestInterface $request, ClockInterface $clock): ?array
    {
        $params = $request->getQueryParams();
        $today = $clock->now();

        $to = isset($params['to']) && is_string($params['to']) ? $params['to'] : $today->format('Y-m-d');
        $from = isset($params['from']) && is_string($params['from'])
            ? $params['from']
            : $today->modify('-29 days')->format('Y-m-d');

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
