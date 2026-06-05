<?php

declare(strict_types=1);

namespace NeneServe\Settings;

final readonly class GetSmtpSettingsOutput
{
    public function __construct(
        public ?SmtpSettingsRecord $record,
    ) {
    }
}
