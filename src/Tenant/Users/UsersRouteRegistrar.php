<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Users;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class UsersRouteRegistrar
{
    public function __construct(
        private ListUsersHandler $listHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $listHandler = $this->listHandler;

        $router->get('/admin/users', static fn (ServerRequestInterface $request) => $listHandler->handle($request));
    }
}
