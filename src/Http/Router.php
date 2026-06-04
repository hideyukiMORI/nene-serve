<?php

declare(strict_types=1);

namespace NeneServe\Http;

/**
 * Exact-match + single-segment `{param}` router. Handlers are
 * `callable(Request): Response`; resolved path params are injected into the
 * Request (see {@see Request::param()}).
 *
 * NENE2 reuse target (docs/development/nene2-compliance.md): replaced by the
 * framework Router once the NENE2 packages are available.
 */
final class Router
{
    /** @var list<array{method: string, regex: string, params: list<string>, handler: callable(Request): Response}> */
    private array $routes = [];

    /** @param callable(Request): Response $handler */
    public function add(string $method, string $pattern, callable $handler): void
    {
        $params = [];
        $regex = preg_replace_callback(
            '#\{([a-z_]+)\}#',
            static function (array $m) use (&$params): string {
                $params[] = $m[1];

                return '(?P<' . $m[1] . '>[^/]+)';
            },
            $pattern,
        );

        $this->routes[] = [
            'method' => strtoupper($method),
            'regex' => '#^' . $regex . '$#',
            'params' => $params,
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): ?Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($request->method)) {
                continue;
            }
            if (preg_match($route['regex'], $request->path, $matches) !== 1) {
                continue;
            }

            $params = [];
            foreach ($route['params'] as $name) {
                $params[$name] = rawurldecode((string) $matches[$name]);
            }

            return ($route['handler'])($request->withParams($params));
        }

        return null;
    }
}
