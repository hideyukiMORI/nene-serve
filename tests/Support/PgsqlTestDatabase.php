<?php

declare(strict_types=1);

namespace NeneServe\Tests\Support;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use Throwable;

/**
 * Connects the integration suite to a real PostgreSQL database (configured via
 * `PGSQL_TEST_*` env), with the production PDO settings — native prepared
 * statements. Exercises the PostgreSQL branch of {@see \NeneServe\Support\SqlDialect}
 * (ON CONFLICT upsert, CAST date) on the real engine (PG support, issue #120).
 *
 * Returns null when PostgreSQL is not configured or not reachable (e.g. the host
 * lacks `pdo_pgsql`), so the tests skip and `composer test` stays serverless. CI
 * applies the translated schema (scripts/mysql-to-pgsql.php) before the suite.
 */
final class PgsqlTestDatabase
{
    public static function fromEnv(): ?PdoDatabaseQueryExecutor
    {
        $host = getenv('PGSQL_TEST_HOST');

        if (!is_string($host) || $host === '' || !extension_loaded('pdo_pgsql')) {
            return null;
        }

        $config = new DatabaseConfig(
            url: null,
            environment: 'test',
            adapter: 'pgsql',
            host: $host,
            port: (int) (self::env('PGSQL_TEST_PORT') ?? '5432'),
            name: self::env('PGSQL_TEST_DB') ?? 'nene_serve_test',
            user: self::env('PGSQL_TEST_USER') ?? 'postgres',
            password: self::env('PGSQL_TEST_PASSWORD') ?? '',
            charset: 'utf8',
        );

        $executor = new PdoDatabaseQueryExecutor(new PdoConnectionFactory($config));

        try {
            $executor->fetchOne('SELECT 1');
        } catch (Throwable) {
            return null;
        }

        return $executor;
    }

    private static function env(string $name): ?string
    {
        $value = getenv($name);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
