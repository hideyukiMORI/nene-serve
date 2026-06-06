<?php

declare(strict_types=1);

namespace NeneServe\Tests\Support;

use NeneServe\Support\SqlDialect;
use PHPUnit\Framework\TestCase;

final class SqlDialectTest extends TestCase
{
    public function testFromAdapterMapsKnownDriversAndDefaultsToMysql(): void
    {
        self::assertSame(SqlDialect::Mysql, SqlDialect::fromAdapter('mysql'));
        self::assertSame(SqlDialect::Pgsql, SqlDialect::fromAdapter('PGSQL'));
        self::assertSame(SqlDialect::Sqlite, SqlDialect::fromAdapter('sqlite'));
        self::assertSame(SqlDialect::Mysql, SqlDialect::fromAdapter('oracle'));
    }

    public function testDateExpr(): void
    {
        self::assertSame('DATE(occurred_at)', SqlDialect::Mysql->dateExpr('occurred_at'));
        self::assertSame('DATE(occurred_at)', SqlDialect::Sqlite->dateExpr('occurred_at'));
        self::assertSame('CAST(occurred_at AS date)', SqlDialect::Pgsql->dateExpr('occurred_at'));
    }

    public function testMysqlUpsertUsesOnDuplicateKey(): void
    {
        $sql = SqlDialect::Mysql->upsert('users', ['id', 'email', 'role'], ['id'], ['email', 'role']);

        self::assertSame(
            'INSERT INTO users (id, email, role) VALUES (?, ?, ?) AS new ON DUPLICATE KEY UPDATE email = new.email, role = new.role',
            $sql,
        );
    }

    public function testPostgresAndSqliteUpsertUseOnConflict(): void
    {
        $expected = 'INSERT INTO users (id, email, role) VALUES (?, ?, ?) ON CONFLICT (id) DO UPDATE SET email = EXCLUDED.email, role = EXCLUDED.role';

        self::assertSame($expected, SqlDialect::Pgsql->upsert('users', ['id', 'email', 'role'], ['id'], ['email', 'role']));
        self::assertSame($expected, SqlDialect::Sqlite->upsert('users', ['id', 'email', 'role'], ['id'], ['email', 'role']));
    }
}
