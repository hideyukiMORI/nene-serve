<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Settings\SmtpSettingsRepositoryInterface;
use NeneServe\Tenant\AuthContext;

/** GET /admin/settings/smtp (operationId `getSmtpSettings`). Requires `manage_settings`. Never returns the password. */
final class GetSmtpSettingsHandler
{
    public function __construct(
        private readonly SmtpSettingsRepositoryInterface $settings,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        $record = $this->settings->find($context->organizationId);
        if ($record === null) {
            return $this->json->ok([
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

        return $this->json->ok($record->toAdminArray() + ['configured' => true]);
    }
}
