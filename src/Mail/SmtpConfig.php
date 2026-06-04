<?php

declare(strict_types=1);

namespace NeneServe\Mail;

/** Resolved SMTP transport settings (the password is already decrypted here). */
final class SmtpConfig
{
    /** @param 'none'|'starttls'|'tls' $encryption */
    public function __construct(
        public readonly string $host,
        public readonly int $port,
        public readonly string $username,
        public readonly string $password,
        public readonly string $fromAddress,
        public readonly string $fromName = '',
        public readonly string $encryption = 'starttls',
        public readonly int $timeoutSeconds = 10,
    ) {
    }

    public function usesAuth(): bool
    {
        return $this->username !== '';
    }
}
