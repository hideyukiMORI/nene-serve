<?php

declare(strict_types=1);

namespace NeneServe\Settings;

use Nene2\Http\JsonResponseFactory;
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
        private GetSmtpSettingsUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $record = $this->useCase->execute()->record;

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
