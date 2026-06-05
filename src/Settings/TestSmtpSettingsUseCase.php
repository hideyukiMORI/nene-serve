<?php

declare(strict_types=1);

namespace NeneServe\Settings;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Mail\Email;
use NeneServe\Mail\MailerException;
use NeneServe\Mail\MailerFactoryInterface;
use NeneServe\Mail\SmtpConfig;
use NeneServe\Support\Crypto;
use NeneServe\Support\CryptoException;
use NeneServe\Tenant\UserRepositoryInterface;

/**
 * Sends a test email through the saved SMTP config to the acting operator;
 * failure-isolated (502 on transport error). Audited.
 */
final readonly class TestSmtpSettingsUseCase implements TestSmtpSettingsUseCaseInterface
{
    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private SmtpSettingsRepositoryInterface $settings,
        private Crypto $crypto,
        private MailerFactoryInterface $mailerFactory,
        private UserRepositoryInterface $users,
        private DatabaseTransactionManagerInterface $transactions,
        private RequestScopedHolder $organizationId,
    ) {
    }

    public function execute(TestSmtpSettingsInput $input): TestSmtpSettingsOutput
    {
        $organizationId = $this->organizationId->get();

        $record = $this->settings->find($organizationId);

        if ($record === null || $record->host === '') {
            throw new SmtpNotConfiguredException('SMTP is not configured.');
        }

        $user = $this->users->findByIdInOrganization($input->actorUserId, $organizationId);
        $recipient = $user !== null ? $user->email : $record->fromAddress;

        try {
            $password = $record->passwordEncrypted !== null ? $this->crypto->decrypt($record->passwordEncrypted) : '';
        } catch (CryptoException $e) {
            throw new EncryptionUnavailableException('At-rest encryption is not configured.', 0, $e);
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
            throw new SmtpTestFailedException($e->getMessage(), 0, $e);
        }

        $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($organizationId, $input, $recipient): void {
                (new PdoAuditLog($tx))->record(
                    $organizationId,
                    $input->actorUserId,
                    'settings.smtp_tested',
                    'smtp_settings',
                    $organizationId,
                    ['recipient' => $recipient],
                );
            },
        );

        return new TestSmtpSettingsOutput($recipient);
    }
}
