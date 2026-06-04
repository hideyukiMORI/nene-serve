<?php

declare(strict_types=1);

namespace NeneServe\Storage;

/**
 * Local filesystem object store (var/uploads). Keys are opaque ids — files are
 * written WITHOUT a guessable/executable extension and live OUTSIDE the web
 * root, served only through the streaming route (never executed by the runtime).
 */
final class LocalStorage implements StorageInterface
{
    public function __construct(
        private readonly string $baseDir,
    ) {
    }

    public function put(string $key, string $bytes): void
    {
        if (!is_dir($this->baseDir)) {
            mkdir($this->baseDir, 0o750, true);
        }
        $path = $this->path($key);
        if (file_put_contents($path, $bytes, LOCK_EX) === false) {
            throw new StorageException('Failed to write object ' . $key . '.');
        }
    }

    public function get(string $key): ?string
    {
        $path = $this->path($key);
        if (!is_file($path)) {
            return null;
        }
        $bytes = file_get_contents($path);

        return $bytes === false ? null : $bytes;
    }

    public function exists(string $key): bool
    {
        return is_file($this->path($key));
    }

    private function path(string $key): string
    {
        // Keys are server-generated opaque ids; reject anything with path separators.
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $key)) {
            throw new StorageException('Invalid storage key.');
        }

        return rtrim($this->baseDir, '/') . '/' . $key;
    }
}
