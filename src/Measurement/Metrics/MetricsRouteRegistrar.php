<?php

declare(strict_types=1);

namespace NeneServe\Measurement\Metrics;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class MetricsRouteRegistrar
{
    public function __construct(
        private GetMetricsHandler $getHandler,
        private ExportMetricsHandler $exportHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $getHandler = $this->getHandler;
        $exportHandler = $this->exportHandler;

        // Export is registered before the bare metrics route; the router matches
        // the more specific path regardless, but keep them grouped.
        $router->get('/admin/metrics/export', static fn (ServerRequestInterface $request) => $exportHandler->handle($request));
        $router->get('/admin/metrics', static fn (ServerRequestInterface $request) => $getHandler->handle($request));
    }
}
