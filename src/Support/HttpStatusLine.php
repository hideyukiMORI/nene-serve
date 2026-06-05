<?php

declare(strict_types=1);

namespace NeneServe\Support;

/**
 * Extracts the HTTP status code from the `$http_response_header` array that the
 * PHP HTTP stream wrapper populates after a {@see file_get_contents()} call.
 * Shared by the outbound service clients (Records / Deal / Invoice).
 */
final class HttpStatusLine
{
    /** @param list<string> $headers */
    public static function statusCode(array $headers): int
    {
        foreach ($headers as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $m) === 1) {
                return (int) $m[1];
            }
        }

        return 0;
    }
}
