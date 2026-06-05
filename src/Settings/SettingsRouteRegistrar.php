<?php

declare(strict_types=1);

namespace NeneServe\Settings;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class SettingsRouteRegistrar
{
    public function __construct(
        private GetSmtpSettingsHandler $getSmtpHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $getSmtpHandler = $this->getSmtpHandler;

        $router->get('/admin/settings/smtp', static fn (ServerRequestInterface $request) => $getSmtpHandler->handle($request));
    }
}
