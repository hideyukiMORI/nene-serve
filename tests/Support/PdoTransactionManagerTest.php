<?php

declare(strict_types=1);

namespace NeneServe\Tests\Support;

use NeneServe\Support\PdoTransactionManager;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Proves the production atomicity guarantee (ADR 0022 §3): if the unit of work
 * throws — e.g. the audit write fails — the accompanying mutation is rolled back,
 * so there is no committed mutation without its audit. Uses SQLite for a real
 * transactional backend.
 */
final class PdoTransactionManagerTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE t (id INTEGER PRIMARY KEY)');
    }

    public function testCommitPersists(): void
    {
        $tx = new PdoTransactionManager($this->pdo);

        $tx->transactional(function (): void {
            $this->pdo->exec('INSERT INTO t (id) VALUES (1)');
        });

        self::assertSame(1, $this->countRows());
    }

    public function testThrowingWorkRollsBackTheMutation(): void
    {
        $tx = new PdoTransactionManager($this->pdo);

        try {
            $tx->transactional(function (): void {
                $this->pdo->exec('INSERT INTO t (id) VALUES (1)'); // the "mutation"
                throw new RuntimeException('audit failed');          // the "audit" fails
            });
            self::fail('exception should propagate');
        } catch (RuntimeException $e) {
            self::assertSame('audit failed', $e->getMessage());
        }

        self::assertSame(0, $this->countRows(), 'mutation must be rolled back');
        self::assertFalse($this->pdo->inTransaction());
    }

    private function countRows(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM t');

        return $stmt === false ? -1 : (int) $stmt->fetchColumn();
    }
}
