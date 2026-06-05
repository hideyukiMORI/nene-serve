<?php

declare(strict_types=1);

namespace NeneServe\Tests\Settings;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RequestScopedHolder;
use Nene2\Validation\ValidationException;
use NeneServe\Mail\Email;
use NeneServe\Mail\MailerException;
use NeneServe\Mail\MailerFactoryInterface;
use NeneServe\Mail\MailerInterface;
use NeneServe\Mail\SmtpConfig;
use NeneServe\Settings\SmtpNotConfiguredException;
use NeneServe\Settings\SmtpSettingsRecord;
use NeneServe\Settings\SmtpSettingsRepositoryInterface;
use NeneServe\Settings\SmtpTestFailedException;
use NeneServe\Settings\TestSmtpSettingsHandler;
use NeneServe\Settings\TestSmtpSettingsUseCase;
use NeneServe\Settings\UpdateSmtpSettingsHandler;
use NeneServe\Settings\UpdateSmtpSettingsUseCase;
use NeneServe\Support\Crypto;
use NeneServe\Tenant\Auth\AdminAuthMiddleware;
use NeneServe\Tenant\Role;
use NeneServe\Tenant\User;
use NeneServe\Tenant\UserRepositoryInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class SmtpSettingsWriteTest extends TestCase
{
    private const CLAIMS = ['org' => 'org-acme', 'role' => 'org_admin', 'sub' => 'u-1'];

    public function testUpdateRejectsMissingHost(): void
    {
        $this->expectException(ValidationException::class);
        $this->update('{"port":587,"from_address":"no-reply@acme.test"}', $this->repo(null));
    }

    public function testUpdatePersistsAndReturnsConfigured(): void
    {
        $response = $this->update(
            '{"host":"mail.acme.test","port":587,"from_address":"no-reply@acme.test","encryption":"starttls"}',
            $this->repo(null),
        );

        self::assertSame(200, $response->getStatusCode());

        /** @var array{configured: bool, host: string} $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertTrue($body['configured']);
        self::assertSame('mail.acme.test', $body['host']);
    }

    public function testTestThrowsWhenNotConfigured(): void
    {
        $this->expectException(SmtpNotConfiguredException::class);
        $this->test($this->repo(null), $this->mailer(false));
    }

    public function testTestEndpointSendsAndReturnsSent(): void
    {
        $response = $this->test($this->repo($this->configuredRecord()), $this->mailer(false));

        self::assertSame(200, $response->getStatusCode());

        /** @var array{sent: bool} $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertTrue($body['sent']);
    }

    public function testTestThrowsOnMailerFailure(): void
    {
        $this->expectException(SmtpTestFailedException::class);
        $this->test($this->repo($this->configuredRecord()), $this->mailer(true));
    }

    private function update(string $json, SmtpSettingsRepositoryInterface $settings): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $useCase = new UpdateSmtpSettingsUseCase($settings, new Crypto(), $this->transactions(), $this->orgId());
        $handler = new UpdateSmtpSettingsHandler($useCase, new JsonResponseFactory($psr17, $psr17), new ProblemDetailsResponseFactory($psr17, $psr17));

        $request = $psr17->createServerRequest('PUT', '/admin/settings/smtp')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($psr17->createStream($json))
            ->withAttribute(AdminAuthMiddleware::CLAIMS_ATTRIBUTE, self::CLAIMS);

        return $handler->handle($request);
    }

    private function test(SmtpSettingsRepositoryInterface $settings, MailerFactoryInterface $mailerFactory): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $useCase = new TestSmtpSettingsUseCase($settings, new Crypto(), $mailerFactory, $this->users(), $this->transactions(), $this->orgId());
        $handler = new TestSmtpSettingsHandler($useCase, new JsonResponseFactory($psr17, $psr17), new ProblemDetailsResponseFactory($psr17, $psr17));

        $request = $psr17->createServerRequest('POST', '/admin/settings/smtp/test')
            ->withAttribute(AdminAuthMiddleware::CLAIMS_ATTRIBUTE, self::CLAIMS);

        return $handler->handle($request);
    }

    private function configuredRecord(): SmtpSettingsRecord
    {
        return new SmtpSettingsRecord('org-acme', 'mail.acme.test', 587, 'mailer', null, 'no-reply@acme.test', 'Acme', 'starttls');
    }

    /** @return RequestScopedHolder<string> */
    private function orgId(): RequestScopedHolder
    {
        /** @var RequestScopedHolder<string> $holder */
        $holder = new RequestScopedHolder();
        $holder->set('org-acme');

        return $holder;
    }

    private function transactions(): DatabaseTransactionManagerInterface
    {
        return new class () implements DatabaseTransactionManagerInterface {
            public function transactional(callable $callback): mixed
            {
                $executor = new class () implements DatabaseQueryExecutorInterface {
                    public function execute(string $sql, array $parameters = []): int
                    {
                        return 0;
                    }

                    public function insert(string $sql, array $parameters = []): int
                    {
                        return 0;
                    }

                    public function lastInsertId(): int
                    {
                        return 0;
                    }

                    public function fetchOne(string $sql, array $parameters = []): ?array
                    {
                        return null;
                    }

                    public function fetchAll(string $sql, array $parameters = []): array
                    {
                        return [];
                    }
                };

                return $callback($executor);
            }
        };
    }

    private function repo(?SmtpSettingsRecord $record): SmtpSettingsRepositoryInterface
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

    private function mailer(bool $fails): MailerFactoryInterface
    {
        return new class ($fails) implements MailerFactoryInterface {
            public function __construct(private readonly bool $fails)
            {
            }

            public function fromConfig(SmtpConfig $config): MailerInterface
            {
                return new class ($this->fails) implements MailerInterface {
                    public function __construct(private readonly bool $fails)
                    {
                    }

                    public function send(Email $email): void
                    {
                        if ($this->fails) {
                            throw new MailerException('transport error');
                        }
                    }
                };
            }
        };
    }

    private function users(): UserRepositoryInterface
    {
        $user = new User('u-1', 'org-acme', 'admin@acme.test', Role::OrgAdmin, password_hash('x', PASSWORD_DEFAULT));

        return new class ($user) implements UserRepositoryInterface {
            public function __construct(private readonly ?User $user)
            {
            }

            public function findByIdInOrganization(string $userId, string $organizationId): ?User
            {
                return $this->user;
            }

            public function findByEmailInOrganization(string $email, string $organizationId): ?User
            {
                return null;
            }

            public function save(User $user): void
            {
            }

            public function listByOrganization(string $organizationId, int $limit, int $offset): array
            {
                return [];
            }

            public function findByIdAcrossTenants(string $userId): ?User
            {
                return null;
            }

            public function listAll(int $limit, int $offset): array
            {
                return [];
            }
        };
    }
}
