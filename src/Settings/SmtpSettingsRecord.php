<?php

declare(strict_types=1);

namespace NeneServe\Settings;

/**
 * Persistence shape for a tenant's SMTP configuration. `passwordEncrypted` is the
 * at-rest ciphertext (Support\Crypto) or null when unset — the plaintext password
 * never lives in this object or in any serialized output.
 */
final class SmtpSettingsRecord
{
    /** @param 'none'|'starttls'|'tls' $encryption */
    public function __construct(
        public readonly string $organizationId,
        public readonly string $host,
        public readonly int $port,
        public readonly string $username,
        public readonly ?string $passwordEncrypted,
        public readonly string $fromAddress,
        public readonly string $fromName,
        public readonly string $encryption,
    ) {
    }

    public function hasPassword(): bool
    {
        return $this->passwordEncrypted !== null && $this->passwordEncrypted !== '';
    }

    /**
     * Admin projection — never includes the password (not even masked ciphertext).
     *
     * @return array{host: string, port: int, username: string, from_address: string, from_name: string, encryption: string, has_password: bool}
     */
    public function toAdminArray(): array
    {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'username' => $this->username,
            'from_address' => $this->fromAddress,
            'from_name' => $this->fromName,
            'encryption' => $this->encryption,
            'has_password' => $this->hasPassword(),
        ];
    }
}
