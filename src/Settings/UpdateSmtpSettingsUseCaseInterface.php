<?php

declare(strict_types=1);

namespace NeneServe\Settings;

interface UpdateSmtpSettingsUseCaseInterface
{
    /**
     * @throws EncryptionUnavailableException when a new password cannot be encrypted at rest
     */
    public function execute(UpdateSmtpSettingsInput $input): UpdateSmtpSettingsOutput;
}
