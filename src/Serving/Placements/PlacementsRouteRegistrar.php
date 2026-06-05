<?php

declare(strict_types=1);

namespace NeneServe\Serving\Placements;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class PlacementsRouteRegistrar
{
    public function __construct(
        private ListPlacementsHandler $listHandler,
        private GetPlacementHandler $getHandler,
        private CreatePlacementHandler $createHandler,
        private ArchivePlacementHandler $archiveHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $listHandler = $this->listHandler;
        $getHandler = $this->getHandler;
        $createHandler = $this->createHandler;
        $archiveHandler = $this->archiveHandler;

        $router->get('/admin/placements', static fn (ServerRequestInterface $request) => $listHandler->handle($request));
        $router->get('/admin/placements/{id}', static fn (ServerRequestInterface $request) => $getHandler->handle($request));
        $router->post('/admin/placements', static fn (ServerRequestInterface $request) => $createHandler->handle($request));
        $router->post('/admin/placements/{id}/archive', static fn (ServerRequestInterface $request) => $archiveHandler->handle($request));
    }
}
