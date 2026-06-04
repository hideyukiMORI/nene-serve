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
    /** @var list<array{org: string, date: string, placement: string, creative: string, bucket: ?string, erased: bool}> */
    private array $impressions = [];

    /** @var list<array{org: string, date: string, placement: string, creative: string}> */
    private array $clicks = [];

    /** @var list<array{org: string, date: string, placement: string, filled: bool}> */
    private array $serves = [];

    public function recordImpression(ImpressionEvent $event): void
    {
        $this->impressions[] = [
            'org' => $event->organizationId,
            'date' => substr($event->occurredAt, 0, 10),
            'placement' => $event->placementId,
            'creative' => $event->creativeId,
            'bucket' => $event->visitorBucket,
            'erased' => false,
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

    public function recordServeRequest(string $organizationId, string $placementId, bool $filled): void
    {
        $this->serves[] = [
            'org' => $organizationId,
            'date' => gmdate('Y-m-d'),
            'placement' => $placementId,
            'filled' => $filled,
        ];
    }

    public function dailyFillRates(string $organizationId, string $fromDate, string $toDate): array
    {
        return FillRateAggregator::aggregate($this->serves, $organizationId, $fromDate, $toDate);
    }

    public function dailyMetrics(string $organizationId, string $fromDate, string $toDate): array
    {
        $impressions = array_map(
            static fn (array $r): array => ['org' => $r['org'], 'date' => $r['date'], 'placement' => $r['placement'], 'creative' => $r['creative']],
            $this->impressions,
        );

        return MetricsAggregator::aggregate($impressions, $this->clicks, $organizationId, $fromDate, $toDate);
    }

    public function exportVisitorData(string $organizationId, string $visitorBucket): array
    {
        $out = [];
        foreach ($this->impressions as $row) {
            if ($row['org'] === $organizationId && $row['bucket'] === $visitorBucket && !$row['erased']) {
                $out[] = [
                    'type' => 'impression',
                    'date' => $row['date'],
                    'placement_id' => $row['placement'],
                    'creative_id' => $row['creative'],
                ];
            }
        }

        return $out;
    }

    public function eraseVisitor(string $organizationId, string $visitorBucket): int
    {
        $count = 0;
        foreach ($this->impressions as $i => $row) {
            if ($row['org'] === $organizationId && $row['bucket'] === $visitorBucket && !$row['erased']) {
                $this->impressions[$i]['erased'] = true;
                $this->impressions[$i]['bucket'] = null; // forget the visitor link; keep the count
                ++$count;
            }
        }

        return $count;
    }
}
