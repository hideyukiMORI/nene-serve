<?php

declare(strict_types=1);

namespace NeneServe\Settings;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /admin/settings/smtp/test (operationId `testSmtpSettings`). Requires
 * `manage_settings`. Sends a test email through the saved SMTP config to the
 * acting operator; failure-isolated (502 on transport error). Audited.
 */
final readonly class TestSmtpSettingsHandler
{
    public function __construct(
        private TestSmtpSettingsUseCaseInterface $test,
        private JsonResponseFactory $response,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $context = AuthContextResolver::fromRequest($request);

        if ($context === null) {
            return $this->problemDetails->create($request, 'unauthorized', 'Unauthorized', 401, 'Authentication is required.');
        }

        $recipient = $this->test->execute(new TestSmtpSettingsInput($context->userId))->recipient;

        return $this->response->create(['sent' => true, 'recipient' => $recipient]);
    }
}
