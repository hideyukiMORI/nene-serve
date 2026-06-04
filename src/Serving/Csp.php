<?php

declare(strict_types=1);

namespace NeneServe\Serving;

/**
 * Strict Content-Security-Policy for the HTML5 sandbox frame (ADR 0021 §4).
 *
 * `sandbox allow-scripts` (no `allow-same-origin`, no `allow-top-navigation`)
 * isolates the bundle and blocks host-page navigation; `script-src 'self'`
 * without `'unsafe-eval'` blocks `eval`; `default-src 'none'` denies arbitrary
 * network egress. Clicks leave only via the registered click redirect.
 */
final class Csp
{
    public static function html5Frame(): string
    {
        return implode('; ', [
            "sandbox allow-scripts",
            "default-src 'none'",
            "script-src 'self'",
            "img-src https: data:",
            "style-src 'unsafe-inline'",
            "base-uri 'none'",
            "form-action 'none'",
            "frame-ancestors *",
        ]);
    }
}
