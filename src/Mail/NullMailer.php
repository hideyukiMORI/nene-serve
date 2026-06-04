<?php

declare(strict_types=1);

namespace NeneServe\Mail;

/**
 * Captures emails instead of sending them. The default in tests and in local
 * boot before SMTP is configured — keeps flows working without a transport.
 */
final class NullMailer implements MailerInterface
{
    /** @var list<Email> */
    private array $sent = [];

    public function send(Email $email): void
    {
        $this->sent[] = $email;
    }

    /** @return list<Email> */
    public function sent(): array
    {
        return $this->sent;
    }

    public function lastTo(): ?string
    {
        $last = end($this->sent);

        return $last === false ? null : $last->toAddress;
    }
}
