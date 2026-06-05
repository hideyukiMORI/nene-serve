<?php

declare(strict_types=1);

namespace NeneServe\Measurement\Dsr;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DsrRouteRegistrar
{
    public function __construct(
        private DataSubjectRequestHandler $handler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $handler = $this->handler;

        $router->post('/admin/data-subject-requests', static fn (ServerRequestInterface $request) => $handler->handle($request));
    }
}
