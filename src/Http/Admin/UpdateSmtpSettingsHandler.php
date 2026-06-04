<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Audit\AuditLogInterface;
use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Settings\SmtpSettingsRecord;
use NeneServe\Settings\SmtpSettingsRepositoryInterface;
use NeneServe\Support\Crypto;
use NeneServe\Support\CryptoException;
use NeneServe\Tenant\AuthContext;

/**
 * PUT /admin/settings/smtp (operationId `updateSmtpSettings`). Requires
 * `manage_settings`. The password is encrypted at rest (Support\Crypto); an
 * omitted/empty password keeps the stored one. Audited (`settings.smtp_updated`).
 */
final class UpdateSmtpSettingsHandler
{
    private const ENCRYPTIONS = ['none', 'starttls', 'tls'];

    public function __construct(
        private readonly SmtpSettingsRepositoryInterface $settings,
        private readonly Crypto $crypto,
        private readonly AuditLogInterface $audit,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        $body = $request->json();
        $host = $body['host'] ?? null;
        $port = $body['port'] ?? null;
        $fromAddress = $body['from_address'] ?? null;
        if (!is_string($host) || $host === '' || !is_int($port) || !is_string($fromAddress) || $fromAddress === '') {
            return $this->json->problem(422, 'validation-failed', 'host, port (int) and from_address are required');
        }

        $encryption = is_string($body['encryption'] ?? null) ? $body['encryption'] : 'starttls';
        if (!in_array($encryption, self::ENCRYPTIONS, true)) {
            return $this->json->problem(422, 'validation-failed', 'encryption must be none, starttls or tls');
        }
        $username = is_string($body['username'] ?? null) ? $body['username'] : '';
        $fromName = is_string($body['from_name'] ?? null) ? $body['from_name'] : '';

        $existing = $this->settings->find($context->organizationId);
        $passwordEncrypted = $existing?->passwordEncrypted;
        $newPassword = $body['password'] ?? null;
        if (is_string($newPassword) && $newPassword !== '') {
            try {
                $passwordEncrypted = $this->crypto->encrypt($newPassword);
            } catch (CryptoException) {
                return $this->json->problem(500, 'encryption-unavailable', 'At-rest encryption is not configured');
            }
        }

        /** @var 'none'|'starttls'|'tls' $encryption */
        $record = new SmtpSettingsRecord(
            $context->organizationId,
            $host,
            $port,
            $username,
            $passwordEncrypted,
            $fromAddress,
            $fromName,
            $encryption,
        );
        $this->settings->save($record);
        $this->audit->record(
            $context->organizationId,
            $context->userId,
            'settings.smtp_updated',
            'smtp_settings',
            $context->organizationId,
            ['host' => $host, 'encryption' => $encryption],
        );

        return $this->json->ok($record->toAdminArray() + ['configured' => true]);
    }
}
