<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

interface SpendSnapshotRepositoryInterface
{
    /** Highest snapshot version for a period (0 if none). */
    public function currentVersion(string $organizationId, string $billingPeriodId): int;

    public function latestForPeriod(string $organizationId, string $billingPeriodId): ?SpendSnapshot;

    /** Append a new immutable snapshot version. */
    public function save(SpendSnapshot $snapshot): void;
}
