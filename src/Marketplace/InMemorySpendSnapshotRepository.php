<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

final class InMemorySpendSnapshotRepository implements SpendSnapshotRepositoryInterface
{
    /** @var list<SpendSnapshot> */
    private array $snapshots = [];

    /** @param list<SpendSnapshot> $snapshots */
    public function __construct(array $snapshots = [])
    {
        $this->snapshots = $snapshots;
    }

    public function currentVersion(string $organizationId, string $billingPeriodId): int
    {
        $max = 0;
        foreach ($this->snapshots as $s) {
            if ($s->organizationId === $organizationId && $s->billingPeriodId === $billingPeriodId) {
                $max = max($max, $s->version);
            }
        }

        return $max;
    }

    public function latestForPeriod(string $organizationId, string $billingPeriodId): ?SpendSnapshot
    {
        $latest = null;
        foreach ($this->snapshots as $s) {
            if ($s->organizationId === $organizationId && $s->billingPeriodId === $billingPeriodId
                && ($latest === null || $s->version > $latest->version)) {
                $latest = $s;
            }
        }

        return $latest;
    }

    public function save(SpendSnapshot $snapshot): void
    {
        $this->snapshots[] = $snapshot;
    }
}
