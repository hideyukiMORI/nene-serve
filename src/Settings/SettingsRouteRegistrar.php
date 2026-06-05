<?php

declare(strict_types=1);

namespace NeneServe\Settings;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class SettingsRouteRegistrar
{
    public function __construct(
        private GetSmtpSettingsHandler $getSmtpHandler,
        private UpdateSmtpSettingsHandler $updateSmtpHandler,
        private TestSmtpSettingsHandler $testSmtpHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $getSmtpHandler = $this->getSmtpHandler;
        $updateSmtpHandler = $this->updateSmtpHandler;
        $testSmtpHandler = $this->testSmtpHandler;

        $router->get('/admin/settings/smtp', static fn (ServerRequestInterface $request) => $getSmtpHandler->handle($request));
        $router->put('/admin/settings/smtp', static fn (ServerRequestInterface $request) => $updateSmtpHandler->handle($request));
        $router->post('/admin/settings/smtp/test', static fn (ServerRequestInterface $request) => $testSmtpHandler->handle($request));
    }
}
