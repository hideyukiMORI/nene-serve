<?php

declare(strict_types=1);

namespace NeneServe\Settings;

final readonly class TestSmtpSettingsInput
{
    public function __construct(
        public string $actorUserId,
    ) {
    }
}
