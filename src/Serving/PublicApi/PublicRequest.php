<?php

declare(strict_types=1);

namespace NeneServe\Serving\PublicApi;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Request helpers for the untrusted public surface. The body parser is lenient
 * (an empty body yields an empty array) because beacons may carry their token in
 * the query string rather than a JSON body.
 */
final class PublicRequest
{
    public static function clientIp(ServerRequestInterface $request): string
    {
        $params = $request->getServerParams();
        $ip = $params['REMOTE_ADDR'] ?? '';

        return is_string($ip) ? $ip : '';
    }

    /** @return array<string, mixed> */
    public static function jsonBody(ServerRequestInterface $request): array
    {
        $raw = (string) $request->getBody();

        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}
