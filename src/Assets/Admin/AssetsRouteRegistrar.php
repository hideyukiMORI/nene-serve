<?php

declare(strict_types=1);

namespace NeneServe\Assets\Admin;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class AssetsRouteRegistrar
{
    public function __construct(
        private UploadAssetHandler $uploadHandler,
        private GetRecordsAssetHandler $recordsAssetHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $uploadHandler = $this->uploadHandler;
        $recordsAssetHandler = $this->recordsAssetHandler;

        $router->post('/admin/assets', static fn (ServerRequestInterface $request) => $uploadHandler->handle($request));
        $router->get('/admin/records-assets/{ref}', static fn (ServerRequestInterface $request) => $recordsAssetHandler->handle($request));
    }
}
