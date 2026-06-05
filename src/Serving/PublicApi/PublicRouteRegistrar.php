<?php

declare(strict_types=1);

namespace NeneServe\Serving\PublicApi;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

/** Public serve surface `/public/*` — no auth; origin-gated, throttled. */
final readonly class PublicRouteRegistrar
{
    public function __construct(
        private ServeHandler $serveHandler,
        private RecordImpressionHandler $impressionHandler,
        private RedirectClickHandler $clickHandler,
        private CreativeFrameHandler $frameHandler,
        private AssetHandler $assetHandler,
        private RecordConversionHandler $conversionHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $serveHandler = $this->serveHandler;
        $impressionHandler = $this->impressionHandler;
        $clickHandler = $this->clickHandler;
        $frameHandler = $this->frameHandler;
        $assetHandler = $this->assetHandler;
        $conversionHandler = $this->conversionHandler;

        $router->get('/public/placements/{public_placement_key}/serve', static fn (ServerRequestInterface $request) => $serveHandler->handle($request));
        $router->post('/public/events/impression', static fn (ServerRequestInterface $request) => $impressionHandler->handle($request));
        $router->get('/public/clicks/{click_token}', static fn (ServerRequestInterface $request) => $clickHandler->handle($request));
        $router->get('/public/frames/{frame_token}', static fn (ServerRequestInterface $request) => $frameHandler->handle($request));
        $router->get('/public/assets/{id}', static fn (ServerRequestInterface $request) => $assetHandler->handle($request));
        $router->post('/public/events/conversion', static fn (ServerRequestInterface $request) => $conversionHandler->handle($request));
    }
}
