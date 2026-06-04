<?php

declare(strict_types=1);

namespace NeneServe\Measurement;

/**
 * In-memory append-only event store for tests. Production uses
 * {@see PdoEventStore}; the live boot uses {@see FileEventStore} so events
 * persist across the separate beacon/redirect/export HTTP requests.
 */
final class InMemoryEventStore implements EventStoreInterface
{
    /** @var list<array{org: string, date: string, placement: string, creative: string}> */
    private array $impressions = [];

    /** @var list<array{org: string, date: string, placement: string, creative: string}> */
    private array $clicks = [];

    public function recordImpression(ImpressionEvent $event): void
    {
        $this->impressions[] = [
            'org' => $event->organizationId,
            'date' => substr($event->occurredAt, 0, 10),
            'placement' => $event->placementId,
            'creative' => $event->creativeId,
        ];
    }

    public function recordClick(ClickEvent $event): void
    {
        $this->clicks[] = [
            'org' => $event->organizationId,
            'date' => substr($event->occurredAt, 0, 10),
            'placement' => $event->placementId,
            'creative' => $event->creativeId,
        ];
    }

    public function dailyMetrics(string $organizationId, string $fromDate, string $toDate): array
    {
        return MetricsAggregator::aggregate($this->impressions, $this->clicks, $organizationId, $fromDate, $toDate);
    }
}
