<?php

declare(strict_types=1);

namespace NeneServe\Measurement;

/**
 * File-backed append-only event store so impressions/clicks recorded in one
 * request are visible to the CSV export in another, on a single-server dev boot.
 * One JSON file guarded by an exclusive lock; production uses {@see PdoEventStore}.
 */
final class FileEventStore implements EventStoreInterface
{
    public function __construct(
        private readonly string $path,
    ) {
    }

    public function recordImpression(ImpressionEvent $event): void
    {
        $this->append('impressions', [
            'org' => $event->organizationId,
            'date' => substr($event->occurredAt, 0, 10),
            'placement' => $event->placementId,
            'creative' => $event->creativeId,
        ]);
    }

    public function recordClick(ClickEvent $event): void
    {
        $this->append('clicks', [
            'org' => $event->organizationId,
            'date' => substr($event->occurredAt, 0, 10),
            'placement' => $event->placementId,
            'creative' => $event->creativeId,
        ]);
    }

    public function dailyMetrics(string $organizationId, string $fromDate, string $toDate): array
    {
        $state = $this->read();

        return MetricsAggregator::aggregate($state['impressions'], $state['clicks'], $organizationId, $fromDate, $toDate);
    }

    /** @param array{org: string, date: string, placement: string, creative: string} $row */
    private function append(string $bucket, array $row): void
    {
        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            mkdir($dir, 0o775, true);
        }

        $handle = fopen($this->path, 'c+');
        if ($handle === false) {
            return;
        }

        try {
            flock($handle, LOCK_EX);
            $raw = stream_get_contents($handle);
            /** @var array{impressions?: list<mixed>, clicks?: list<mixed>} $state */
            $state = $raw === false || $raw === '' ? [] : (json_decode($raw, true) ?: []);
            $state += ['impressions' => [], 'clicks' => []];
            $state[$bucket][] = $row;

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, (string) json_encode($state));
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /** @return array{impressions: list<array{org: string, date: string, placement: string, creative: string}>, clicks: list<array{org: string, date: string, placement: string, creative: string}>} */
    private function read(): array
    {
        if (!is_file($this->path)) {
            return ['impressions' => [], 'clicks' => []];
        }
        $raw = (string) file_get_contents($this->path);
        /** @var array{impressions?: list<array{org: string, date: string, placement: string, creative: string}>, clicks?: list<array{org: string, date: string, placement: string, creative: string}>} $state */
        $state = $raw === '' ? [] : (json_decode($raw, true) ?: []);

        return ['impressions' => $state['impressions'] ?? [], 'clicks' => $state['clicks'] ?? []];
    }
}
