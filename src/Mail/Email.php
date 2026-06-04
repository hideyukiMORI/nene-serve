<?php

declare(strict_types=1);

namespace NeneServe\Mail;

/**
 * A minimal, text-only transactional email (invites, test mail). No HTML/track
 * pixels by default — operational mail, not marketing (privacy by default).
 */
final class Email
{
    public function __construct(
        public readonly string $toAddress,
        public readonly string $subject,
        public readonly string $textBody,
    ) {
    }
}
