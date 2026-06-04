<?php

declare(strict_types=1);

namespace NeneServe\Mail;

final class SmtpMailerFactory implements MailerFactoryInterface
{
    public function fromConfig(SmtpConfig $config): MailerInterface
    {
        return new SmtpMailer($config);
    }
}
