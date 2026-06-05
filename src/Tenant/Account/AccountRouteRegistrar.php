<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Account;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class AccountRouteRegistrar
{
    public function __construct(
        private CurrentUserHandler $currentUserHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $currentUserHandler = $this->currentUserHandler;

        $router->get('/admin/me', static fn (ServerRequestInterface $request) => $currentUserHandler->handle($request));
    }
}
