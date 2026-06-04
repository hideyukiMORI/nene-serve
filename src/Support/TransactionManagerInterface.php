<?php

declare(strict_types=1);

namespace NeneServe\Support;

/**
 * Runs a unit of work atomically so a governed mutation and its audit record
 * commit together — there is no committed mutation without its audit (ADR 0022
 * §3, audit-and-data-integrity §2). If the work throws, everything rolls back.
 */
interface TransactionManagerInterface
{
    /**
     * @template T
     * @param callable():T $work
     * @return T
     */
    public function transactional(callable $work): mixed;
}
