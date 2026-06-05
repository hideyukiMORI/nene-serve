<?php

declare(strict_types=1);

namespace NeneServe\Settings;

final readonly class UpdateSmtpSettingsInput
{
    /**
     * @param 'none'|'starttls'|'tls' $encryption
     */
    public function __construct(
        public string $actorUserId,
        public string $host,
        public int $port,
        public string $username,
        public ?string $rawPassword,
        public string $fromAddress,
        public string $fromName,
        public string $encryption,
    ) {
    }
}
