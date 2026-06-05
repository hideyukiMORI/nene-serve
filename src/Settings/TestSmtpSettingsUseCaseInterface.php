<?php

declare(strict_types=1);

namespace NeneServe\Settings;

interface TestSmtpSettingsUseCaseInterface
{
    /**
     * @throws SmtpNotConfiguredException     when SMTP has not been configured
     * @throws EncryptionUnavailableException when the stored password cannot be decrypted
     * @throws SmtpTestFailedException        when the test email cannot be sent
     */
    public function execute(TestSmtpSettingsInput $input): TestSmtpSettingsOutput;
}
