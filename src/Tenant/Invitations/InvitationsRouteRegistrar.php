<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Invitations;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class InvitationsRouteRegistrar
{
    public function __construct(
        private PreviewInvitationHandler $previewHandler,
        private AcceptInvitationHandler $acceptHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $previewHandler = $this->previewHandler;
        $acceptHandler = $this->acceptHandler;

        $router->post('/admin/invitations/accept', static fn (ServerRequestInterface $request) => $acceptHandler->handle($request));
        $router->get('/admin/invitations/{token}', static fn (ServerRequestInterface $request) => $previewHandler->handle($request));
    }
}
