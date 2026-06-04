<?php

declare(strict_types=1);

namespace NeneServe\Storage;

/** Object storage for uploaded bytes, addressed by an opaque key. */
interface StorageInterface
{
    public function put(string $key, string $bytes): void;

    public function get(string $key): ?string;

    public function exists(string $key): bool;
}
