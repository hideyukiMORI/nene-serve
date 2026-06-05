<?php

declare(strict_types=1);

namespace NeneServe\Settings;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Support\Crypto;
use NeneServe\Support\CryptoException;

/**
 * Persists a tenant's SMTP configuration. The password is encrypted at rest
 * (Support\Crypto); an omitted/empty password keeps the stored one. The save and
 * its audit entry commit together (NENE2 transaction pattern).
 */
final readonly class UpdateSmtpSettingsUseCase implements UpdateSmtpSettingsUseCaseInterface
{
    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private SmtpSettingsRepositoryInterface $settings,
        private Crypto $crypto,
        private DatabaseTransactionManagerInterface $transactions,
        private RequestScopedHolder $organizationId,
    ) {
    }

    public function execute(UpdateSmtpSettingsInput $input): UpdateSmtpSettingsOutput
    {
        $organizationId = $this->organizationId->get();

        $existing = $this->settings->find($organizationId);
        $passwordEncrypted = $existing?->passwordEncrypted;

        if ($input->rawPassword !== null && $input->rawPassword !== '') {
            try {
                $passwordEncrypted = $this->crypto->encrypt($input->rawPassword);
            } catch (CryptoException $e) {
                throw new EncryptionUnavailableException('At-rest encryption is not configured.', 0, $e);
            }
        }

        $record = new SmtpSettingsRecord(
            $organizationId,
            $input->host,
            $input->port,
            $input->username,
            $passwordEncrypted,
            $input->fromAddress,
            $input->fromName,
            $input->encryption,
        );

        $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($record, $input, $organizationId): void {
                (new PdoSmtpSettingsRepository($tx))->save($record);
                (new PdoAuditLog($tx))->record(
                    $organizationId,
                    $input->actorUserId,
                    'settings.smtp_updated',
                    'smtp_settings',
                    $organizationId,
                    ['host' => $record->host, 'encryption' => $record->encryption],
                );
            },
        );

        return new UpdateSmtpSettingsOutput($record);
    }
}
