<?php

declare(strict_types=1);

namespace NeneServe\Serving;

/**
 * Redirect-target safety (ADR 0019/0021): a destination must be `https`, or
 * `http` only for localhost during development. Enforced both when a creative is
 * accepted and again at redirect time (defense in depth) so a stored bad value
 * can never become an open redirect.
 */
final class DestinationUrl
{
    public static function isSafe(string $url): bool
    {
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        $scheme = strtolower($parts['scheme']);
        // parse_url() returns an IPv6 host wrapped in brackets (e.g. "[::1]");
        // strip them so the loopback allowlist below can match.
        $host = strtolower(trim($parts['host'], '[]'));

        if ($scheme === 'https') {
            return true;
        }

        return $scheme === 'http' && in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }
}
