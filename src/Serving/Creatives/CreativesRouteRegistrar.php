<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CreativesRouteRegistrar
{
    public function __construct(
        private ListCreativesHandler $listHandler,
        private GetCreativeHandler $getHandler,
        private ReviewQueueHandler $reviewQueueHandler,
        private CreateCreativeHandler $createHandler,
        private ReviseCreativeHandler $reviseHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $listHandler = $this->listHandler;
        $getHandler = $this->getHandler;
        $reviewQueueHandler = $this->reviewQueueHandler;
        $createHandler = $this->createHandler;
        $reviseHandler = $this->reviseHandler;

        $router->get('/admin/creatives', static fn (ServerRequestInterface $request) => $listHandler->handle($request));
        $router->get('/admin/creatives/{id}', static fn (ServerRequestInterface $request) => $getHandler->handle($request));
        $router->get('/admin/review-queue', static fn (ServerRequestInterface $request) => $reviewQueueHandler->handle($request));
        $router->post('/admin/creatives', static fn (ServerRequestInterface $request) => $createHandler->handle($request));
        $router->post('/admin/creatives/{id}/revise', static fn (ServerRequestInterface $request) => $reviseHandler->handle($request));
    }
}
