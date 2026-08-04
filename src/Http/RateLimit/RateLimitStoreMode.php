<?php

declare(strict_types=1);

namespace NeneServe\Http\RateLimit;

use Nene2\Config\AppEnvironment;

/**
 * Which {@see \Nene2\Middleware\RateLimitStorageInterface} implementation the
 * runtime binds, resolved from `NENE_SERVE_RATE_LIMIT_STORE`.
 *
 * `database` (the default) is shared across processes and hosts. `memory` is the
 * framework's per-process array store: convenient when running without a
 * database, and unable to enforce anything under one-process-per-request — the
 * counter is rebuilt empty every time (#199).
 *
 * Production therefore refuses `memory` at boot rather than serving traffic with
 * an inert limiter. This mirrors {@see \Nene2\Auth\GuardedJwtSecretResolver}
 * (#138): the convenient path stays available outside production, behind an
 * explicit opt-in, and production always fails closed.
 */
enum RateLimitStoreMode: string
{
    case Database = 'database';
    case Memory = 'memory';

    public const ENV_KEY = 'NENE_SERVE_RATE_LIMIT_STORE';

    /**
     * Resolves the configured mode, refusing a non-shared store in production.
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

        $mode = self::tryFrom($value) ?? throw new RateLimitStorageException(sprintf(
            '%s must be one of: %s (got "%s").',
            self::ENV_KEY,
            implode(', ', array_column(self::cases(), 'value')),
            $configured,
        ));

        if ($mode === self::Memory && $environment === AppEnvironment::Production) {
            throw new RateLimitStorageException(sprintf(
                '%s=memory cannot enforce a rate limit in production: the counter lives in one PHP process '
                . 'and is rebuilt on every request, so the limit is never reached. Remove the setting to use '
                . 'the shared database store.',
                self::ENV_KEY,
            ));
        }

        return $mode;
    }
}
