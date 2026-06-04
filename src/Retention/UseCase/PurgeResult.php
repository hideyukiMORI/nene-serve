<?php

declare(strict_types=1);

namespace NeneServe\Retention\UseCase;

/** Outcome of a retention purge run for one tenant. */
final class PurgeResult
{
    public function __construct(
        public readonly bool $blockedByLegalHold,
        public readonly int $purgedEvents,
    ) {
    }
}
