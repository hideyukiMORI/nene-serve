<?php

declare(strict_types=1);

namespace NeneServe\Support;

use PDO;
use Throwable;

/**
 * Wraps a unit of work in a single DB transaction on the shared PDO. On any
 * throwable the transaction is rolled back, so a failed audit write undoes the
 * mutation it accompanies (audit-and-data-integrity §2).
 */
final class PdoTransactionManager implements TransactionManagerInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function transactional(callable $work): mixed
    {
        // Reuse an outer transaction if one is already open (no nesting).
        if ($this->pdo->inTransaction()) {
            return $work();
        }

        $this->pdo->beginTransaction();
        try {
            $result = $work();
            $this->pdo->commit();

            return $result;
        } catch (Throwable $e) {
            $this->pdo->rollBack();

            throw $e;
        }
    }
}
