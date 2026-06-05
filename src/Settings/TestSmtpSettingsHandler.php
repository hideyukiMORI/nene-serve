<?php

declare(strict_types=1);

namespace NeneServe\Settings;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Audit\AuditLogInterface;
use NeneServe\Mail\Email;
use NeneServe\Mail\MailerException;
use NeneServe\Mail\MailerFactoryInterface;
use NeneServe\Mail\SmtpConfig;
use NeneServe\Support\Crypto;
use NeneServe\Support\CryptoException;
use NeneServe\Tenant\Auth\AuthContextResolver;
use NeneServe\Tenant\UserRepositoryInterface;
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
        private SmtpSettingsRepositoryInterface $settings,
        private Crypto $crypto,
        private MailerFactoryInterface $mailerFactory,
        private UserRepositoryInterface $users,
        private AuditLogInterface $audit,
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

        if ($record === null || $record->host === '') {
            return $this->problemDetails->create($request, 'smtp-not-configured', 'SMTP not configured', 422, 'SMTP is not configured.');
        }

        $user = $this->users->findByIdInOrganization($context->userId, $context->organizationId);
        $recipient = $user !== null ? $user->email : $record->fromAddress;

        try {
            $password = $record->passwordEncrypted !== null ? $this->crypto->decrypt($record->passwordEncrypted) : '';
        } catch (CryptoException) {
            return $this->problemDetails->create($request, 'encryption-unavailable', 'Encryption unavailable', 500, 'At-rest encryption is not configured.');
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
            return $this->problemDetails->create($request, 'smtp-test-failed', 'SMTP test failed', 502, $e->getMessage());
        }

        $this->audit->record(
            $context->organizationId,
            $context->userId,
            'settings.smtp_tested',
            'smtp_settings',
            $context->organizationId,
            ['recipient' => $recipient],
        );

        return $this->response->create(['sent' => true, 'recipient' => $recipient]);
    }
}
