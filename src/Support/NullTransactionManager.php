<?php

declare(strict_types=1);

namespace NeneServe\Support;

/**
 * No-op transaction manager for the in-memory stores (tests / local boot). The
 * production atomicity guarantee comes from {@see PdoTransactionManager}.
 */
final class NullTransactionManager implements TransactionManagerInterface
{
    public function transactional(callable $work): mixed
    {
        return $work();
    }
}
