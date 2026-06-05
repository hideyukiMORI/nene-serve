<?php

declare(strict_types=1);

namespace NeneServe\Auth;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class AuthRouteRegistrar
{
    public function __construct(
        private LoginHandler $loginHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $loginHandler = $this->loginHandler;

        $router->post('/admin/login', static fn (ServerRequestInterface $request) => $loginHandler->handle($request));
    }
}
