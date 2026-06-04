<?php

declare(strict_types=1);

namespace NeNe\Serve\Http;

/**
 * Application kernel: registers routes and turns a (method, path) pair into a
 * Response. Kept transport-agnostic so it is unit-testable without a server
 * (see tests/Http/HealthTest.php).
 */
final class Kernel
{
    public const VERSION = '0.1.0-scaffold';

    private readonly Router $router;
    private readonly JsonResponseFactory $json;

    public function __construct()
    {
        $this->json = new JsonResponseFactory();
        $this->router = new Router();
        $this->registerRoutes();
    }

    public function handle(string $method, string $path): Response
    {
        $response = $this->router->dispatch($method, $path);
        if ($response !== null) {
            return $response;
        }

        return $this->json->problem(
            404,
            'not-found',
            'Resource not found',
            sprintf('No route for %s %s.', strtoupper($method), $path),
        );
    }

    private function registerRoutes(): void
    {
        $health = new HealthController($this->json);
        $this->router->add('GET', '/health', $health->show(...));
    }
}
