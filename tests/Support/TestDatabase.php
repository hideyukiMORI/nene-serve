<?php

declare(strict_types=1);

namespace NeneServe\Tests\Support;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use RuntimeException;

/**
 * In-memory SQLite harness for repository tests. A single
 * {@see PdoDatabaseQueryExecutor} caches one connection, so the loaded schema and
 * seeded rows persist for the life of the executor. The portable SELECT paths of
 * the production repositories run unchanged; the MySQL-specific upsert in their
 * save() does not, so tests seed via {@see self::seed()} (plain INSERT).
 */
final class TestDatabase
{
    /**
     * Builds an executor over an in-memory SQLite DB with the given schema
     * fixtures (file names under tests/Support/schema, without extension).
     */
    public static function withSchema(string ...$tables): PdoDatabaseQueryExecutor
    {
        $executor = new PdoDatabaseQueryExecutor(new PdoConnectionFactory(DatabaseConfig::sqlite(':memory:')));

        foreach ($tables as $table) {
            $path = __DIR__ . '/schema/' . $table . '.sql';
            if (!is_file($path)) {
                throw new RuntimeException("Missing schema fixture: {$path}");
            }
            foreach (self::statements((string) file_get_contents($path)) as $statement) {
                $executor->execute($statement);
            }
        }

        return $executor;
    }

    /**
     * Inserts a single row from a column => value map (portable, dialect-neutral).
     *
     * @param array<string, scalar|null> $row
     */
    public static function seed(PdoDatabaseQueryExecutor $executor, string $table, array $row): void
    {
        $columns = array_keys($row);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $executor->execute(
            sprintf('INSERT INTO %s (%s) VALUES (%s)', $table, implode(', ', $columns), $placeholders),
            array_values($row),
        );
    }

    /** @return list<string> */
    private static function statements(string $sql): array
    {
        // Strip full-line SQL comments so they don't ride along with the next
        // statement in the chunk.
        $sql = (string) preg_replace('/^\s*--.*$/m', '', $sql);

        $out = [];
        foreach (preg_split('/;\R/s', trim($sql)) ?: [] as $chunk) {
            $statement = trim($chunk);
            if ($statement !== '') {
                $out[] = $statement;
            }
        }

        return $out;
    }
}
