<?php

declare(strict_types=1);

namespace NeneServe\Settings;

interface GetSmtpSettingsUseCaseInterface
{
    public function execute(): GetSmtpSettingsOutput;
}
