<?php

declare(strict_types=1);

namespace NeneServe\Settings;

final readonly class UpdateSmtpSettingsOutput
{
    public function __construct(
        public SmtpSettingsRecord $record,
    ) {
    }
}
