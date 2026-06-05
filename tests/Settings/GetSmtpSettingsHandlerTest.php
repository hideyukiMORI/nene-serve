<?php

declare(strict_types=1);

namespace NeneServe\Tests\Settings;

use Nene2\Http\JsonResponseFactory;
use NeneServe\Settings\GetSmtpSettingsHandler;
use NeneServe\Settings\GetSmtpSettingsOutput;
use NeneServe\Settings\GetSmtpSettingsUseCaseInterface;
use NeneServe\Settings\SmtpSettingsRecord;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class GetSmtpSettingsHandlerTest extends TestCase
{
    public function testReturnsStoredSettingsWithoutPassword(): void
    {
        $record = new SmtpSettingsRecord('org-acme', 'mail.acme.test', 587, 'mailer', 'cipher', 'no-reply@acme.test', 'Acme', 'starttls');

        $response = $this->handle($this->useCaseReturning($record));

        self::assertSame(200, $response->getStatusCode());

        /** @var array{configured: bool, has_password: bool, host: string} $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertTrue($body['configured']);
        self::assertTrue($body['has_password']);
        self::assertSame('mail.acme.test', $body['host']);
        self::assertArrayNotHasKey('password', $body);
        self::assertArrayNotHasKey('password_encrypted', $body);
    }

    public function testReturnsUnconfiguredDefaultsWhenAbsent(): void
    {
        $response = $this->handle($this->useCaseReturning(null));

        self::assertSame(200, $response->getStatusCode());

        /** @var array{configured: bool} $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertFalse($body['configured']);
    }

    private function handle(GetSmtpSettingsUseCaseInterface $useCase): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $handler = new GetSmtpSettingsHandler($useCase, new JsonResponseFactory($psr17, $psr17));

        return $handler->handle($psr17->createServerRequest('GET', '/admin/settings/smtp'));
    }

    private function useCaseReturning(?SmtpSettingsRecord $record): GetSmtpSettingsUseCaseInterface
    {
        return new class ($record) implements GetSmtpSettingsUseCaseInterface {
            public function __construct(private readonly ?SmtpSettingsRecord $record)
            {
            }

            public function execute(): GetSmtpSettingsOutput
            {
                return new GetSmtpSettingsOutput($this->record);
            }
        };
    }
}
