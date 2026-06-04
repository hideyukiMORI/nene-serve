<?php

declare(strict_types=1);

namespace NeneServe\Tests\Mail;

use NeneServe\Mail\Email;
use NeneServe\Mail\NullMailer;
use PHPUnit\Framework\TestCase;

final class NullMailerTest extends TestCase
{
    public function testCapturesSentEmails(): void
    {
        $mailer = new NullMailer();
        self::assertSame([], $mailer->sent());
        self::assertNull($mailer->lastTo());

        $mailer->send(new Email('ops@acme.test', 'Invite', 'Set your password: https://…'));

        self::assertCount(1, $mailer->sent());
        self::assertSame('ops@acme.test', $mailer->lastTo());
    }
}
