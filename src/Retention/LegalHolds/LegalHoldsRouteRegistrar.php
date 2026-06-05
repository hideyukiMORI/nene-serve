<?php

declare(strict_types=1);

namespace NeneServe\Retention\LegalHolds;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class LegalHoldsRouteRegistrar
{
    public function __construct(
        private PlaceLegalHoldHandler $placeHandler,
        private ReleaseLegalHoldHandler $releaseHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $placeHandler = $this->placeHandler;
        $releaseHandler = $this->releaseHandler;

        $router->post('/admin/legal-holds', static fn (ServerRequestInterface $request) => $placeHandler->handle($request));
        $router->post('/admin/legal-holds/{id}/release', static fn (ServerRequestInterface $request) => $releaseHandler->handle($request));
    }
}
