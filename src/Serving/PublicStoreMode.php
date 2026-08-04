<?php

declare(strict_types=1);

namespace NeneServe\Serving;

use Nene2\Config\AppEnvironment;

/**
 * Where the public serving surface keeps the state that has to outlive a single
 * request — the opaque tokens (ADR 0019) and the frequency cap counts — resolved
 * from `NENE_SERVE_PUBLIC_STORE`.
 *
 * `database` (the default) is shared by every process and host. `file` is the
 * JSON-file pair under `var/`: correct on one host, and silently wrong the
 * moment a second one appears, because each host would issue tokens the other
 * cannot redeem and count caps the other cannot see.
 *
 * Production refuses `file` at boot. The file stores are not broken the way the
 * per-process rate limit store was (#199) — they work on a single host — but the
 * failure they do have is invisible: the deployment that adds the second host
 * looks fine and starts dropping clicks. Refusing at boot moves that discovery
 * from a production incident to a configuration error.
 *
 * Deliberately one variable for both stores rather than two: they hold the same
 * flow's state and there is no deployment where one should be shared and the
 * other not. Same guarded shape as {@see \NeneServe\Http\RateLimit\RateLimitStoreMode}
 * and {@see \Nene2\Auth\GuardedJwtSecretResolver} (#138) — the convenient path
 * stays available outside production behind an explicit opt-in, and production
 * always fails closed.
 */
enum PublicStoreMode: string
{
    case Database = 'database';
    case File = 'file';

    public const ENV_KEY = 'NENE_SERVE_PUBLIC_STORE';

    /**
     * Resolves the configured mode, refusing single-host storage in production.
     *
     * An empty value selects {@see self::Database}; an unrecognised one is a
     * configuration error rather than a silent fallback, so a typo cannot land a
     * deployment on a store nobody chose.
     */
    public static function resolve(string $configured, AppEnvironment $environment): self
    {
        $value = strtolower(trim($configured));

        if ($value === '') {
            return self::Database;
        }

        $mode = self::tryFrom($value) ?? throw new PublicStoreException(sprintf(
            '%s must be one of: %s (got "%s").',
            self::ENV_KEY,
            implode(', ', array_column(self::cases(), 'value')),
            $configured,
        ));

        if ($mode === self::File && $environment === AppEnvironment::Production) {
            throw new PublicStoreException(sprintf(
                '%s=file keeps public tokens and frequency counts in local JSON files, which only one host can '
                . 'read. A second host would issue tokens the first cannot redeem. Remove the setting to use the '
                . 'shared database store.',
                self::ENV_KEY,
            ));
        }

        return $mode;
    }
}
