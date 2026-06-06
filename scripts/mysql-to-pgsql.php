<?php

declare(strict_types=1);

/**
 * Emits PostgreSQL DDL from the MySQL migrations (PG support, issue #120). The
 * MySQL migrations in database/migrations are the single source of truth; this
 * prints their PostgreSQL translation to stdout.
 *
 * Usage: php scripts/mysql-to-pgsql.php [migrationsDir] > schema.pgsql
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use NeneServe\Support\MysqlToPgsqlTranslator;

$dir = $argv[1] ?? (dirname(__DIR__) . '/database/migrations');
$files = glob($dir . '/*.sql') ?: [];
sort($files);

$translator = new MysqlToPgsqlTranslator();
$pieces = [];

foreach ($files as $file) {
    $pieces[] = '-- ' . basename($file);
    $pieces[] = $translator->translateFile((string) file_get_contents($file));
}

echo implode("\n\n", $pieces) . "\n";
