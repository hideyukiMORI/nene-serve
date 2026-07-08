<?php

declare(strict_types=1);

namespace NeneServe\Measurement;

/**
 * Derives a hashed, rotating visitor bucket (privacy P4): never a raw cookie,
 * IP, or email. Salted per org and per UTC day so buckets cannot be correlated
 * across organizations (no cross-publisher fingerprint, N3) or across days. Only
 * computed when consent permits (caller's responsibility).
 *
 * `$day` is the UTC day (`Y-m-d`) — callers derive it from their injected
 * {@see \Nene2\Http\ClockInterface}, never from the wall clock directly.
 */
final class VisitorBucket
{
    public static function derive(string $organizationId, string $clientIp, string $userAgent, string $day): string
    {
        return substr(hash('sha256', $organizationId . '|' . $day . '|' . $clientIp . '|' . $userAgent), 0, 32);
    }
}
