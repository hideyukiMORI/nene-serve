<?php

declare(strict_types=1);

namespace NeneServe\Mail;

interface MailerInterface
{
    /** @throws MailerException on delivery failure. */
    public function send(Email $email): void;
}
