<?php

declare(strict_types=1);

namespace NeneServe\Tests\Settings;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Settings\GetSmtpSettingsHandler;
use NeneServe\Settings\SmtpSettingsRecord;
use NeneServe\Settings\SmtpSettingsRepositoryInterface;
use NeneServe\Tenant\Auth\AdminAuthMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class GetSmtpSettingsHandlerTest extends TestCase
{
    public function testReturnsStoredSettingsWithoutPassword(): void
    {
        $record = new SmtpSettingsRecord('org-acme', 'mail.acme.test', 587, 'mailer', 'cipher', 'no-reply@acme.test', 'Acme', 'starttls');

        $response = $this->handle($this->repositoryReturning($record), ['org' => 'org-acme', 'role' => 'org_admin', 'sub' => 'u-1']);

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
        $response = $this->handle($this->repositoryReturning(null), ['org' => 'org-acme', 'role' => 'org_admin', 'sub' => 'u-1']);

        self::assertSame(200, $response->getStatusCode());

        /** @var array{configured: bool} $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertFalse($body['configured']);
    }

    /**
     * @param array<string, mixed> $claims
     */
    private function handle(SmtpSettingsRepositoryInterface $settings, array $claims): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $handler = new GetSmtpSettingsHandler(
            $settings,
            new JsonResponseFactory($psr17, $psr17),
            new ProblemDetailsResponseFactory($psr17, $psr17),
        );

        $request = $psr17->createServerRequest('GET', '/admin/settings/smtp')
            ->withAttribute(AdminAuthMiddleware::CLAIMS_ATTRIBUTE, $claims);

        return $handler->handle($request);
    }

    private function repositoryReturning(?SmtpSettingsRecord $record): SmtpSettingsRepositoryInterface
    {
        return new class ($record) implements SmtpSettingsRepositoryInterface {
            public function __construct(private readonly ?SmtpSettingsRecord $record)
            {
            }

            public function find(string $organizationId): ?SmtpSettingsRecord
            {
                return $this->record;
            }

            public function save(SmtpSettingsRecord $record): void
            {
            }
        };
    }
}
