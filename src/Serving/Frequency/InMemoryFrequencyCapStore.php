<?php

declare(strict_types=1);

namespace NeneServe\Serving\Frequency;

final class InMemoryFrequencyCapStore implements FrequencyCapStoreInterface
{
    /** @var array<string, int> keyed by "placementId|visitorBucket" */
    private array $counts = [];

    public function count(string $placementId, string $visitorBucket): int
    {
        return $this->counts[$placementId . '|' . $visitorBucket] ?? 0;
    }

    public function increment(string $placementId, string $visitorBucket): void
    {
        $key = $placementId . '|' . $visitorBucket;
        $this->counts[$key] = ($this->counts[$key] ?? 0) + 1;
    }
}
