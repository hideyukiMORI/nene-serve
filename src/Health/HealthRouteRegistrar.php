<?php

declare(strict_types=1);

namespace NeneServe\Health;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class HealthRouteRegistrar
{
    public function __construct(
        private HealthHandler $handler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $handler = $this->handler;

        $router->get('/health', static fn (ServerRequestInterface $request) => $handler->handle($request));
    }
}
