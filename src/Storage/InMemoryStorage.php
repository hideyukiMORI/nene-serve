<?php

declare(strict_types=1);

namespace NeneServe\Storage;

final class InMemoryStorage implements StorageInterface
{
    /** @var array<string, string> */
    private array $objects = [];

    public function put(string $key, string $bytes): void
    {
        $this->objects[$key] = $bytes;
    }

    public function get(string $key): ?string
    {
        return $this->objects[$key] ?? null;
    }

    public function exists(string $key): bool
    {
        return isset($this->objects[$key]);
    }
}
