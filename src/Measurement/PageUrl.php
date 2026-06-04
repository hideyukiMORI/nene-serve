<?php

declare(strict_types=1);

namespace NeneServe\Measurement;

/**
 * Truncates a page URL to scheme://host/path, dropping query/fragment so tokens
 * and query secrets are never stored (privacy P9/N1).
 */
final class PageUrl
{
    public static function truncate(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        return $parts['scheme'] . '://' . $parts['host'] . ($parts['path'] ?? '');
    }
}
