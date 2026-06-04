<?php

declare(strict_types=1);

namespace NeneServe\Tests\Http;

use NeneServe\Http\Kernel;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Mail\MailerFactoryInterface;
use NeneServe\Mail\MailerInterface;
use NeneServe\Mail\NullMailer;
use NeneServe\Mail\SmtpConfig;
use NeneServe\Settings\InMemorySmtpSettingsRepository;
use NeneServe\Settings\SmtpSettingsRecord;
use NeneServe\Support\Crypto;
use NeneServe\Support\DevFixtures;
use NeneServe\Tenant\InMemoryInvitationRepository;
use PHPUnit\Framework\TestCase;

/**
 * Invite-link provisioning (#3): admin creates a user → invite email with a
 * single-use token → invitee sets their password → can log in. Tenant-scoped,
 * capability-gated, unauthenticated acceptance.
 */
final class UserProvisioningTest extends TestCase
{
    private Kernel $kernel;
    private NullMailer $mailer;

    protected function setUp(): void
    {
        putenv('APP_ENCRYPTION_KEY=' . base64_encode(str_repeat('k', 32)));
        $this->mailer = new NullMailer();
        $factory = new class ($this->mailer) implements MailerFactoryInterface {
            public function __construct(private readonly NullMailer $mailer)
            {
            }

            public function fromConfig(SmtpConfig $config): MailerInterface
            {
                return $this->mailer;
            }
        };

        // Pre-configure SMTP so the invite email is actually attempted.
        $settings = new InMemorySmtpSettingsRepository();
        $settings->save(new SmtpSettingsRecord('org-acme', 'mailpit', 1025, '', null, 'no-reply@acme.test', 'Acme', 'none'));

        $this->kernel = new Kernel(
            smtpSettings: $settings,
            mailerFactory: $factory,
            crypto: new Crypto(),
            invitations: new InMemoryInvitationRepository(),
        );
    }

    protected function tearDown(): void
    {
        putenv('APP_ENCRYPTION_KEY');
    }

    public function testInviteThenAcceptThenLogin(): void
    {
        $admin = $this->login('admin@acme.test');

        // 1) Admin creates a user → invite email sent with a tokenised link.
        $created = $this->send('POST', '/admin/users', $admin, ['email' => 'newbie@acme.test', 'role' => 'analyst']);
        self::assertSame(201, $created->status);
        $body = $this->decode($created->body);
        self::assertSame('newbie@acme.test', $body['email']);
        self::assertTrue($body['invite_email_sent']);
        self::assertStringNotContainsString('token', $created->body, 'raw token must not be returned by the API');

        // Extract the raw token from the emailed link.
        $sent = $this->mailer->sent();
        self::assertCount(1, $sent);
        self::assertSame('newbie@acme.test', $sent[0]->toAddress);
        self::assertSame(1, preg_match('/set-password\?token=([a-f0-9]+)/', $sent[0]->textBody, $m));
        $token = $m[1] ?? '';
        self::assertNotSame('', $token);

        // 2) The token previews as valid (unauthenticated).
        $preview = $this->kernel->handle(new Request('GET', '/admin/invitations/' . $token));
        self::assertSame(200, $preview->status);
        self::assertSame('newbie@acme.test', $this->decode($preview->body)['email']);

        // 3) Accept + set password (unauthenticated).
        $accept = $this->kernel->handle(new Request('POST', '/admin/invitations/accept', [], [], (string) json_encode([
            'token' => $token, 'password' => 'a-strong-password',
        ])));
        self::assertSame(200, $accept->status);

        // 4) The token is single-use — a second accept fails.
        $again = $this->kernel->handle(new Request('POST', '/admin/invitations/accept', [], [], (string) json_encode([
            'token' => $token, 'password' => 'another-password',
        ])));
        self::assertSame(404, $again->status);

        // 5) The new user can now log in.
        $loginResponse = $this->kernel->handle(new Request('POST', '/admin/login', [], [], (string) json_encode([
            'organization' => 'acme', 'email' => 'newbie@acme.test', 'password' => 'a-strong-password',
        ])));
        self::assertSame(200, $loginResponse->status);
    }

    public function testDuplicateEmailIsRejected(): void
    {
        $admin = $this->login('admin@acme.test');
        $response = $this->send('POST', '/admin/users', $admin, ['email' => 'admin@acme.test', 'role' => 'analyst']);
        self::assertSame(422, $response->status);
        self::assertStringEndsWith('/problems/user-invalid', $this->decode($response->body)['type']);
    }

    public function testWeakPasswordIsRejected(): void
    {
        $admin = $this->login('admin@acme.test');
        $created = $this->send('POST', '/admin/users', $admin, ['email' => 'weak@acme.test', 'role' => 'analyst']);
        self::assertSame(201, $created->status);
        preg_match('/token=([a-f0-9]+)/', $this->mailer->sent()[0]->textBody, $m);

        $accept = $this->kernel->handle(new Request('POST', '/admin/invitations/accept', [], [], (string) json_encode([
            'token' => $m[1] ?? '', 'password' => 'short',
        ])));
        self::assertSame(422, $accept->status);
        self::assertStringEndsWith('/problems/weak-password', $this->decode($accept->body)['type']);
    }

    public function testCreateUserRequiresManageUsers(): void
    {
        $analyst = $this->login('analyst@acme.test');
        $response = $this->send('POST', '/admin/users', $analyst, ['email' => 'x@acme.test', 'role' => 'analyst']);
        self::assertSame(403, $response->status);
    }

    /** @param array<string, mixed> $body */
    private function send(string $method, string $path, string $token, array $body): Response
    {
        return $this->kernel->handle(new Request(
            $method,
            $path,
            ['authorization' => 'Bearer ' . $token],
            [],
            (string) json_encode($body),
        ));
    }

    private function login(string $email): string
    {
        $response = $this->kernel->handle(new Request('POST', '/admin/login', [], [], (string) json_encode([
            'organization' => 'acme',
            'email' => $email,
            'password' => DevFixtures::PASSWORD,
        ])));
        self::assertSame(200, $response->status, 'login failed for ' . $email);

        return (string) $this->decode($response->body)['token'];
    }

    /** @return array<string, mixed> */
    private function decode(string $body): array
    {
        /** @var array<string, mixed> $data */
        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        return $data;
    }
}
