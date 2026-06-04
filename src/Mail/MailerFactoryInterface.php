<?php

declare(strict_types=1);

namespace NeneServe\Mail;

/** Builds a transport for a resolved SMTP config (lets tests swap in a fake). */
interface MailerFactoryInterface
{
    public function fromConfig(SmtpConfig $config): MailerInterface;
}
