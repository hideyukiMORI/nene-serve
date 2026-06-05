<?php

declare(strict_types=1);

namespace NeneServe\Service\Api;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

/** Service surface `/api/*` — opaque scoped service-token auth (api-security §5). */
final readonly class ServiceRouteRegistrar
{
    public function __construct(
        private ListPlacementsHandler $listPlacements,
        private GetMetricsHandler $getMetrics,
        private ExportMetricsHandler $exportMetrics,
        private ProposeChangeHandler $proposeChange,
        private ApplyChangeHandler $applyChange,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $listPlacements = $this->listPlacements;
        $getMetrics = $this->getMetrics;
        $exportMetrics = $this->exportMetrics;
        $proposeChange = $this->proposeChange;
        $applyChange = $this->applyChange;

        $router->get('/api/placements', static fn (ServerRequestInterface $request) => $listPlacements->handle($request));
        $router->get('/api/metrics/export', static fn (ServerRequestInterface $request) => $exportMetrics->handle($request));
        $router->get('/api/metrics', static fn (ServerRequestInterface $request) => $getMetrics->handle($request));
        $router->post('/api/delivery-plan-changes', static fn (ServerRequestInterface $request) => $proposeChange->handle($request));
        $router->post('/api/delivery-plan-changes/{token}/apply', static fn (ServerRequestInterface $request) => $applyChange->handle($request));
    }
}
