<?php

declare(strict_types=1);

namespace NeneServe\Settings;

use NeneServe\Mail\SmtpConfig;
use NeneServe\Support\Crypto;
use NeneServe\Support\CryptoException;

/** Builds a ready-to-use {@see SmtpConfig} from a tenant's stored settings (decrypting the password). */
final class SmtpConfigResolver
{
    public function __construct(
        private readonly SmtpSettingsRepositoryInterface $settings,
        private readonly Crypto $crypto,
    ) {
    }

    /** @throws CryptoException when a stored password cannot be decrypted. */
    public function resolve(string $organizationId): ?SmtpConfig
    {
        $record = $this->settings->find($organizationId);
        if ($record === null || $record->host === '') {
            return null;
        }

        $password = $record->passwordEncrypted !== null ? $this->crypto->decrypt($record->passwordEncrypted) : '';

        return new SmtpConfig(
            $record->host,
            $record->port,
            $record->username,
            $password,
            $record->fromAddress,
            $record->fromName,
            $record->encryption,
        );
    }
}
