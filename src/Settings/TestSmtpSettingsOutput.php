<?php

declare(strict_types=1);

namespace NeneServe\Settings;

final readonly class TestSmtpSettingsOutput
{
    public function __construct(
        public string $recipient,
    ) {
    }
}
