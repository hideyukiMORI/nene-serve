<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Deal;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DealRouteRegistrar
{
    public function __construct(
        private HandoffCampaignToDealHandler $handoffHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $handoffHandler = $this->handoffHandler;

        $router->post('/admin/campaigns/{id}/deal-handoff', static fn (ServerRequestInterface $request) => $handoffHandler->handle($request));
    }
}
