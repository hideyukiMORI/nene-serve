<?php

declare(strict_types=1);

namespace NeneServe\Settings;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /admin/settings/smtp (operationId `getSmtpSettings`). Requires
 * `manage_settings` (enforced by the capability middleware). Never returns the
 * password — only whether one is set.
 */
final readonly class GetSmtpSettingsHandler
{
    public function __construct(
        private SmtpSettingsRepositoryInterface $settings,
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

        $record = $this->settings->find($context->organizationId);

        if ($record === null) {
            return $this->response->create([
                'host' => '',
                'port' => 587,
                'username' => '',
                'from_address' => '',
                'from_name' => '',
                'encryption' => 'starttls',
                'has_password' => false,
                'configured' => false,
            ]);
        }

        return $this->response->create($record->toAdminArray() + ['configured' => true]);
    }
}
