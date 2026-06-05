<?php

declare(strict_types=1);

namespace NeneServe\Support;

/**
 * Read-modify-write of a single JSON file under an exclusive lock — the shared
 * scaffolding behind the dev-only file stores (tokens / events / frequency caps;
 * production swaps a shared store behind the same contract). The transform
 * receives the current raw file contents and returns the new contents; the
 * caller owns the JSON (de)serialization and its type shape.
 */
trait LockedJsonFile
{
    /** @param callable(string): string $transform raw current contents → new contents */
    private function withLockedFile(string $path, callable $transform): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0o775, true);
        }

        $handle = fopen($path, 'c+');
        if ($handle === false) {
            return;
        }

        try {
            flock($handle, LOCK_EX);
            $raw = stream_get_contents($handle);

            $next = $transform($raw === false ? '' : $raw);

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, $next);
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
