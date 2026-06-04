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
use NeneServe\Support\Crypto;
use NeneServe\Support\DevFixtures;
use PHPUnit\Framework\TestCase;

/**
 * Admin-managed SMTP settings (manage_settings): save (password encrypted at
 * rest, never returned), get (masked), and the test-send flow through an
 * injected mailer (no real SMTP).
 */
final class SmtpSettingsTest extends TestCase
{
    private Kernel $kernel;
    private NullMailer $mailer;

    protected function setUp(): void
    {
        putenv('APP_ENCRYPTION_KEY=' . base64_encode(str_repeat('k', 32)));
        $this->mailer = new NullMailer();
        $captured = $this->mailer;
        $factory = new class ($captured) implements MailerFactoryInterface {
            public function __construct(private readonly NullMailer $mailer)
            {
            }

            public function fromConfig(SmtpConfig $config): MailerInterface
            {
                return $this->mailer;
            }
        };

        $this->kernel = new Kernel(
            smtpSettings: new InMemorySmtpSettingsRepository(),
            mailerFactory: $factory,
            crypto: new Crypto(),
        );
    }

    protected function tearDown(): void
    {
        putenv('APP_ENCRYPTION_KEY');
    }

    public function testSavesGetsMaskedAndSendsTest(): void
    {
        $token = $this->login('admin@acme.test');

        // Initially unconfigured.
        $initial = $this->decode($this->get('/admin/settings/smtp', $token)->body);
        self::assertFalse($initial['configured']);
        self::assertFalse($initial['has_password']);

        // Save settings with a password.
        $saved = $this->send('PUT', '/admin/settings/smtp', $token, [
            'host' => 'mailpit',
            'port' => 1025,
            'username' => 'apikey',
            'password' => 'super-secret',
            'from_address' => 'no-reply@acme.test',
            'from_name' => 'Acme',
            'encryption' => 'starttls',
        ]);
        self::assertSame(200, $saved->status);
        $body = $this->decode($saved->body);
        self::assertTrue($body['has_password']);
        // The password is never echoed back.
        self::assertStringNotContainsString('super-secret', $saved->body);

        // GET still masks the password and reports configured + has_password.
        $fetched = $this->decode($this->get('/admin/settings/smtp', $token)->body);
        self::assertTrue($fetched['configured']);
        self::assertTrue($fetched['has_password']);
        self::assertArrayNotHasKey('password', $fetched);

        // Test send goes to the operator through the injected mailer.
        $test = $this->send('POST', '/admin/settings/smtp/test', $token, []);
        self::assertSame(200, $test->status);
        self::assertTrue($this->decode($test->body)['sent']);
        self::assertSame('admin@acme.test', $this->mailer->lastTo());
    }

    public function testTestSendWithoutConfigIs422(): void
    {
        $token = $this->login('admin@acme.test');
        $response = $this->send('POST', '/admin/settings/smtp/test', $token, []);

        self::assertSame(422, $response->status);
        self::assertStringEndsWith('/problems/smtp-not-configured', $this->decode($response->body)['type']);
    }

    public function testOmittedPasswordKeepsTheStoredOne(): void
    {
        $token = $this->login('admin@acme.test');
        $this->send('PUT', '/admin/settings/smtp', $token, [
            'host' => 'mailpit', 'port' => 1025, 'password' => 'first-secret',
            'from_address' => 'a@acme.test',
        ]);
        // Second save without a password keeps has_password true.
        $second = $this->send('PUT', '/admin/settings/smtp', $token, [
            'host' => 'mailpit2', 'port' => 2525, 'from_address' => 'a@acme.test',
        ]);
        self::assertTrue($this->decode($second->body)['has_password']);
    }

    public function testNonSettingsRoleIsForbidden(): void
    {
        $token = $this->login('analyst@acme.test');
        $response = $this->get('/admin/settings/smtp', $token);
        self::assertSame(403, $response->status);
    }

    private function get(string $path, string $token): Response
    {
        return $this->kernel->handle(new Request('GET', $path, ['authorization' => 'Bearer ' . $token]));
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
