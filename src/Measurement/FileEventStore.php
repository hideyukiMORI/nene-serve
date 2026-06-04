<?php

declare(strict_types=1);

namespace NeneServe\Measurement;

/**
 * File-backed append-only event store so impressions/clicks recorded in one
 * request are visible to the CSV export / DSR tooling in another, on a
 * single-server dev boot. One JSON file under an exclusive lock; production uses
 * {@see PdoEventStore}.
 */
final class FileEventStore implements EventStoreInterface
{
    public function __construct(
        private readonly string $path,
    ) {
    }

    public function recordImpression(ImpressionEvent $event): void
    {
        $this->mutate(function (array &$state) use ($event): void {
            $state['impressions'][] = [
                'org' => $event->organizationId,
                'date' => substr($event->occurredAt, 0, 10),
                'placement' => $event->placementId,
                'creative' => $event->creativeId,
                'bucket' => $event->visitorBucket,
                'erased' => false,
            ];
        });
    }

    public function recordClick(ClickEvent $event): void
    {
        $this->mutate(function (array &$state) use ($event): void {
            $state['clicks'][] = [
                'org' => $event->organizationId,
                'date' => substr($event->occurredAt, 0, 10),
                'placement' => $event->placementId,
                'creative' => $event->creativeId,
            ];
        });
    }

    public function dailyMetrics(string $organizationId, string $fromDate, string $toDate): array
    {
        $state = $this->read();
        $impressions = array_map(
            static fn (array $r): array => ['org' => $r['org'], 'date' => $r['date'], 'placement' => $r['placement'], 'creative' => $r['creative']],
            $state['impressions'],
        );

        return MetricsAggregator::aggregate($impressions, $state['clicks'], $organizationId, $fromDate, $toDate);
    }

    public function exportVisitorData(string $organizationId, string $visitorBucket): array
    {
        $out = [];
        foreach ($this->read()['impressions'] as $row) {
            if ($row['org'] === $organizationId && ($row['bucket'] ?? null) === $visitorBucket && !($row['erased'] ?? false)) {
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
        $this->mutate(function (array &$state) use ($organizationId, $visitorBucket, &$count): void {
            foreach ($state['impressions'] as $i => $row) {
                if ($row['org'] === $organizationId && ($row['bucket'] ?? null) === $visitorBucket && !($row['erased'] ?? false)) {
                    $state['impressions'][$i]['erased'] = true;
                    $state['impressions'][$i]['bucket'] = null;
                    ++$count;
                }
            }
        });

        return $count;
    }

    /**
     * @param callable(array{impressions: list<array<string, mixed>>, clicks: list<array<string, mixed>>}): void $apply
     */
    private function mutate(callable $apply): void
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
            /** @var array{impressions?: list<array<string, mixed>>, clicks?: list<array<string, mixed>>} $state */
            $state = $raw === false || $raw === '' ? [] : (json_decode($raw, true) ?: []);
            $state += ['impressions' => [], 'clicks' => []];

            $apply($state);

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, (string) json_encode($state));
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /** @return array{impressions: list<array<string, mixed>>, clicks: list<array{org: string, date: string, placement: string, creative: string}>} */
    private function read(): array
    {
        if (!is_file($this->path)) {
            return ['impressions' => [], 'clicks' => []];
        }
        $raw = (string) file_get_contents($this->path);
        /** @var array{impressions?: list<array<string, mixed>>, clicks?: list<array{org: string, date: string, placement: string, creative: string}>} $state */
        $state = $raw === '' ? [] : (json_decode($raw, true) ?: []);

        return ['impressions' => $state['impressions'] ?? [], 'clicks' => $state['clicks'] ?? []];
    }
}
