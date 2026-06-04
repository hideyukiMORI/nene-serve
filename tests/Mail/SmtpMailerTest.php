<?php

declare(strict_types=1);

namespace NeneServe\Tests\Mail;

use NeneServe\Mail\Email;
use NeneServe\Mail\SmtpConfig;
use NeneServe\Mail\SmtpMailer;
use PHPUnit\Framework\TestCase;

/**
 * Integration check against a live SMTP catcher (Mailpit). Skipped unless
 * MAIL_SMTP_TEST=1, so CI needs no mail server; run locally with the docker
 * stack up (Mailpit SMTP on 1025).
 */
final class SmtpMailerTest extends TestCase
{
    public function testSendsThroughMailpit(): void
    {
        if (getenv('MAIL_SMTP_TEST') !== '1') {
            self::markTestSkipped('Set MAIL_SMTP_TEST=1 (with Mailpit on 1025) to run the SMTP integration test.');
        }

        $host = getenv('MAIL_SMTP_HOST') ?: '127.0.0.1';
        $port = (int) (getenv('MAIL_SMTP_PORT') ?: '1025');
        $mailer = new SmtpMailer(new SmtpConfig(
            host: (string) $host,
            port: $port,
            username: '',
            password: '',
            fromAddress: 'no-reply@serve.test',
            fromName: 'NeNe Serve',
            encryption: 'none',
        ));

        $mailer->send(new Email('operator@acme.test', 'テスト送信 / Test', 'Hello from serve.'));
        $this->expectNotToPerformAssertions();
    }
}
