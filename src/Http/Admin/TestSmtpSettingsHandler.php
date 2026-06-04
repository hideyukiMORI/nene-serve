<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Audit\AuditLogInterface;
use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Mail\Email;
use NeneServe\Mail\MailerException;
use NeneServe\Mail\MailerFactoryInterface;
use NeneServe\Mail\SmtpConfig;
use NeneServe\Settings\SmtpSettingsRepositoryInterface;
use NeneServe\Support\Crypto;
use NeneServe\Support\CryptoException;
use NeneServe\Tenant\AuthContext;
use NeneServe\Tenant\UserRepositoryInterface;

/**
 * POST /admin/settings/smtp/test (operationId `testSmtpSettings`). Requires
 * `manage_settings`. Sends a test email through the saved SMTP config to the
 * acting operator; failure-isolated (502 on transport error). Audited.
 */
final class TestSmtpSettingsHandler
{
    public function __construct(
        private readonly SmtpSettingsRepositoryInterface $settings,
        private readonly Crypto $crypto,
        private readonly MailerFactoryInterface $mailerFactory,
        private readonly UserRepositoryInterface $users,
        private readonly AuditLogInterface $audit,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        $record = $this->settings->find($context->organizationId);
        if ($record === null || $record->host === '') {
            return $this->json->problem(422, 'smtp-not-configured', 'SMTP is not configured');
        }

        $user = $this->users->findByIdInOrganization($context->userId, $context->organizationId);
        $recipient = $user !== null ? $user->email : $record->fromAddress;

        try {
            $password = $record->passwordEncrypted !== null ? $this->crypto->decrypt($record->passwordEncrypted) : '';
        } catch (CryptoException) {
            return $this->json->problem(500, 'encryption-unavailable', 'At-rest encryption is not configured');
        }

        $config = new SmtpConfig(
            $record->host,
            $record->port,
            $record->username,
            $password,
            $record->fromAddress,
            $record->fromName,
            $record->encryption,
        );

        try {
            $this->mailerFactory->fromConfig($config)->send(new Email(
                $recipient,
                'NeNe Serve — SMTP test',
                "This is a test email confirming your SMTP configuration works.\n\nNeNe Serve",
            ));
        } catch (MailerException $e) {
            return $this->json->problem(502, 'smtp-test-failed', 'SMTP test failed', $e->getMessage());
        }

        $this->audit->record(
            $context->organizationId,
            $context->userId,
            'settings.smtp_tested',
            'smtp_settings',
            $context->organizationId,
            ['recipient' => $recipient],
        );

        return $this->json->ok(['sent' => true, 'recipient' => $recipient]);
    }
}
