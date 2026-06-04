<?php

declare(strict_types=1);

namespace NeneServe\Http;

/**
 * Minimal exact-match router. Handlers are `callable(Request): Response`.
 *
 * NENE2 reuse target (docs/development/nene2-compliance.md): replaced by the
 * framework Router once the NENE2 packages are available. Path-parameter
 * support lands with the public serve surface in #12.
 */
final class Router
{
    /** @var array<string, array<string, callable(Request): Response>> */
    private array $routes = [];

    /** @param callable(Request): Response $handler */
    public function add(string $method, string $path, callable $handler): void
    {
        $this->routes[strtoupper($method)][$path] = $handler;
    }

    public function dispatch(Request $request): ?Response
    {
        $handler = $this->routes[strtoupper($request->method)][$request->path] ?? null;
        if ($handler === null) {
            return null;
        }

        return $handler($request);
    }
}
