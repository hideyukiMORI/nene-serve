<?php

declare(strict_types=1);

namespace NeneServe\Serving\Frequency;

/**
 * File-backed frequency counter so caps hold across the separate serve/beacon
 * HTTP requests on a single-server dev boot. Production counts from the
 * impressions table by visitor_bucket. JSON file under an exclusive lock.
 */
final class FileFrequencyCapStore implements FrequencyCapStoreInterface
{
    public function __construct(
        private readonly string $path,
    ) {
    }

    public function count(string $placementId, string $visitorBucket): int
    {
        $state = $this->read();

        return (int) ($state[$placementId . '|' . $visitorBucket] ?? 0);
    }

    public function increment(string $placementId, string $visitorBucket): void
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
            /** @var array<string, int> $state */
            $state = $raw === false || $raw === '' ? [] : (json_decode($raw, true) ?: []);
            $key = $placementId . '|' . $visitorBucket;
            $state[$key] = (int) ($state[$key] ?? 0) + 1;

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, (string) json_encode($state));
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /** @return array<string, int> */
    private function read(): array
    {
        if (!is_file($this->path)) {
            return [];
        }
        $raw = (string) file_get_contents($this->path);
        /** @var array<string, int> $state */
        $state = $raw === '' ? [] : (json_decode($raw, true) ?: []);

        return $state;
    }
}
