<?php

declare(strict_types=1);

namespace NeNe\Serve\Http;

/**
 * Minimal exact-match router.
 *
 * NENE2 reuse target (docs/development/nene2-compliance.md): replaced by the
 * framework Router once the NENE2 packages are available. Path-parameter and
 * middleware support land with the three API surfaces in #12.
 */
final class Router
{
    /** @var array<string, array<string, callable():Response>> */
    private array $routes = [];

    public function add(string $method, string $path, callable $handler): void
    {
        $this->routes[strtoupper($method)][$path] = $handler;
    }

    public function dispatch(string $method, string $path): ?Response
    {
        $handler = $this->routes[strtoupper($method)][$path] ?? null;
        if ($handler === null) {
            return null;
        }

        return $handler();
    }
}
