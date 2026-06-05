<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class BillingRouteRegistrar
{
    public function __construct(
        private OpenBillingPeriodHandler $openHandler,
        private CloseBillingPeriodHandler $closeHandler,
        private GetBillingPeriodHandler $getHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $openHandler = $this->openHandler;
        $closeHandler = $this->closeHandler;
        $getHandler = $this->getHandler;

        $router->post('/admin/campaigns/{id}/billing-periods', static fn (ServerRequestInterface $request) => $openHandler->handle($request));
        $router->post('/admin/billing-periods/{id}/close', static fn (ServerRequestInterface $request) => $closeHandler->handle($request));
        $router->get('/admin/billing-periods/{id}', static fn (ServerRequestInterface $request) => $getHandler->handle($request));
    }
}
