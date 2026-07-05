<?php

declare(strict_types=1);

namespace NeneServe\Tests\Support;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Testing\DatabaseTestKit;
use Throwable;

/**
 * Connects the integration suite to a real MySQL database (configured via
 * `MYSQL_TEST_*` env), using the production PDO settings — native prepared
 * statements (`ATTR_EMULATE_PREPARES = false`). This is what exposes
 * dialect-specific bugs the in-memory SQLite {@see TestDatabase} cannot, e.g.
 * a named placeholder reused across a UNION (issue #116).
 *
 * Returns null when MySQL is not configured or not reachable, so the integration
 * tests skip and `composer test` stays serverless. CI applies the real
 * migrations (database/migrations) to the target database before the suite runs.
 */
final class MysqlTestDatabase
{
    public static function fromEnv(): ?DatabaseQueryExecutorInterface
    {
        $host = getenv('MYSQL_TEST_HOST');

        if (!is_string($host) || $host === '') {
            return null;
        }

        $config = new DatabaseConfig(
            url: null,
            environment: 'test',
            adapter: 'mysql',
            host: $host,
            port: (int) (self::env('MYSQL_TEST_PORT') ?? '3306'),
            name: self::env('MYSQL_TEST_DB') ?? 'nene_serve_test',
            user: self::env('MYSQL_TEST_USER') ?? 'root',
            password: self::env('MYSQL_TEST_PASSWORD') ?? '',
            charset: 'utf8mb4',
        );

        $executor = DatabaseTestKit::fromConfig($config)->queryExecutor;

        // The PDO connection is built lazily; probe it so an unreachable server
        // results in a clean skip rather than a mid-test failure.
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
