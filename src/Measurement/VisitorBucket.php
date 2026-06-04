<?php

declare(strict_types=1);

namespace NeneServe\Measurement;

/**
 * Derives a hashed, rotating visitor bucket (privacy P4): never a raw cookie,
 * IP, or email. Salted per org and per UTC day so buckets cannot be correlated
 * across organizations (no cross-publisher fingerprint, N3) or across days. Only
 * computed when consent permits (caller's responsibility).
 */
final class VisitorBucket
{
    public static function derive(string $organizationId, string $clientIp, string $userAgent, ?string $day = null): string
    {
        $day ??= gmdate('Y-m-d');

        return substr(hash('sha256', $organizationId . '|' . $day . '|' . $clientIp . '|' . $userAgent), 0, 32);
    }
}
