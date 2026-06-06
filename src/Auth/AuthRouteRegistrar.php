<?php

declare(strict_types=1);

namespace NeneServe\Auth;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class AuthRouteRegistrar
{
    public function __construct(
        private LoginHandler $loginHandler,
        private TenantContextHandler $tenantContextHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $loginHandler = $this->loginHandler;
        $tenantContextHandler = $this->tenantContextHandler;

        $router->get('/admin/tenant-context', static fn (ServerRequestInterface $request) => $tenantContextHandler->handle($request));
        $router->post('/admin/login', static fn (ServerRequestInterface $request) => $loginHandler->handle($request));
    }
}
