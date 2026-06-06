<?php

declare(strict_types=1);

namespace NeneServe\Support;

/**
 * The active SQL dialect, so repositories can emit portable SQL across the
 * supported drivers (ADR 0006 multi-tenant data; production targets MySQL and
 * PostgreSQL, tests run on SQLite). Resolved from the configured DB adapter and
 * injected into the repositories; MySQL is the default for an unknown adapter so
 * existing behaviour is preserved.
 */
enum SqlDialect: string
{
    case Mysql = 'mysql';
    case Pgsql = 'pgsql';
    case Sqlite = 'sqlite';

    public static function fromAdapter(string $adapter): self
    {
        return self::tryFrom(strtolower(trim($adapter))) ?? self::Mysql;
    }

    /**
     * Truncates a timestamp column to a date. MySQL and SQLite expose `DATE()`;
     * PostgreSQL has no such function and uses a cast.
     */
    public function dateExpr(string $column): string
    {
        return $this === self::Pgsql ? "CAST({$column} AS date)" : "DATE({$column})";
    }

    /**
     * Builds an idempotent upsert (the app DB role has no DELETE/REPLACE, ADR
     * 0022): MySQL uses `INSERT … AS new ON DUPLICATE KEY UPDATE`; PostgreSQL and
     * SQLite use `INSERT … ON CONFLICT (keys) DO UPDATE SET … = EXCLUDED.…`.
     * Values are bound positionally in `$columns` order.
     *
     * @param list<string> $columns       all inserted columns, in bind order
     * @param list<string> $conflictKeys  the unique/PK columns that collide
     * @param list<string> $updateColumns columns to overwrite on conflict
     */
    public function upsert(string $table, array $columns, array $conflictKeys, array $updateColumns): string
    {
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $insert = sprintf('INSERT INTO %s (%s) VALUES (%s)', $table, implode(', ', $columns), $placeholders);

        if ($this === self::Mysql) {
            $set = implode(', ', array_map(static fn (string $c): string => "{$c} = new.{$c}", $updateColumns));

            return "{$insert} AS new ON DUPLICATE KEY UPDATE {$set}";
        }

        $set = implode(', ', array_map(static fn (string $c): string => "{$c} = EXCLUDED.{$c}", $updateColumns));

        return sprintf('%s ON CONFLICT (%s) DO UPDATE SET %s', $insert, implode(', ', $conflictKeys), $set);
    }
}
