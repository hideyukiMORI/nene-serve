<?php

declare(strict_types=1);

use Nene2\Config\ConfigLoader;

require_once __DIR__ . '/vendor/autoload.php';

/*
 * Phinx configuration for NeNe Serve.
 *
 * Scope: this wires phinx for FUTURE migrations only. The numbered raw-SQL files
 * in database/migrations/0001..NNNN_*.sql are the historical baseline and stay
 * the single source of truth for the existing schema (they are also translated to
 * PostgreSQL by scripts/mysql-to-pgsql.php). Phinx only ever scans `*.php`, so
 * those `.sql` baseline files are invisible to it and are left untouched.
 *
 * From now on, add new schema changes as phinx PHP migrations in the same
 * database/migrations directory (e.g. `composer migrations:create -- CreateFoo`).
 * See database/README.md for the bootstrap/ordering contract.
 *
 * Connection settings come from the same NENE2 ConfigLoader the app uses, so
 * phinx targets whatever DB the current .env points at (DB_HOST/DB_NAME/DB_USER/
 * DB_PASSWORD/DB_PORT/DB_ADAPTER, per docs/development/configuration.md).
 */

$database = (new ConfigLoader(__DIR__))->load()->database;

return [
    'paths' => [
        'migrations' => 'database/migrations',
        'seeds' => 'database/seeds',
    ],
    'environments' => [
        'default_environment' => $database->environment,
        $database->environment => $database->usesUrl()
            ? ['url' => $database->url]
            : [
                'adapter' => $database->adapter,
                'host' => $database->host,
                'name' => $database->name,
                'user' => $database->user,
                'pass' => $database->password,
                'port' => $database->port,
                'charset' => $database->charset,
            ],
    ],
    'version_order' => 'creation',
];
