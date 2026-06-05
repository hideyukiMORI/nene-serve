<?php

declare(strict_types=1);

namespace NeneServe\Tests\Tenant\Users;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationException;
use NeneServe\Mail\MailerFactoryInterface;
use NeneServe\Mail\MailerInterface;
use NeneServe\Mail\SmtpConfig;
use NeneServe\Settings\SmtpConfigResolver;
use NeneServe\Settings\SmtpSettingsRecord;
use NeneServe\Settings\SmtpSettingsRepositoryInterface;
use NeneServe\Support\Crypto;
use NeneServe\Tenant\Auth\AdminAuthMiddleware;
use NeneServe\Tenant\AuthContext;
use NeneServe\Tenant\Role;
use NeneServe\Tenant\UseCase\InvitedUser;
use NeneServe\Tenant\UseCase\UserValidationException;
use NeneServe\Tenant\User;
use NeneServe\Tenant\Users\CreateInvitedUserUseCaseInterface;
use NeneServe\Tenant\Users\CreateUserHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class CreateUserHandlerTest extends TestCase
{
    private const CLAIMS = ['org' => 'org-acme', 'role' => 'org_admin', 'sub' => 'u-1'];

    public function testCreatesInvitedUserAndReportsEmailNotSentWhenSmtpAbsent(): void
    {
        $useCase = $this->useCaseReturningInvite();

        $response = $this->handle($useCase, '{"email":"new@acme.test","role":"editor"}', self::CLAIMS);

        self::assertSame(201, $response->getStatusCode());

        /** @var array{email: string, invite_email_sent: bool} $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertSame('new@acme.test', $body['email']);
        self::assertFalse($body['invite_email_sent']);
    }

    public function testRejectsMissingFields(): void
    {
        $this->expectException(ValidationException::class);
        $this->handle($this->useCaseReturningInvite(), '{}', self::CLAIMS);
    }

    public function testPropagatesDomainValidationFailure(): void
    {
        $useCase = new class () implements CreateInvitedUserUseCaseInterface {
            public function execute(AuthContext $actor, string $email, string $role): InvitedUser
            {
                throw new UserValidationException('A user with that email already exists.');
            }
        };

        $this->expectException(UserValidationException::class);
        $this->handle($useCase, '{"email":"dup@acme.test","role":"editor"}', self::CLAIMS);
    }

    public function testRejectsRequestWithoutClaims(): void
    {
        $response = $this->handle($this->useCaseReturningInvite(), '{"email":"new@acme.test","role":"editor"}', null);

        self::assertSame(401, $response->getStatusCode());
    }

    /**
     * @param array<string, mixed>|null $claims
     */
    private function handle(CreateInvitedUserUseCaseInterface $useCase, string $json, ?array $claims): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $handler = new CreateUserHandler(
            $useCase,
            new SmtpConfigResolver($this->settingsRepo(), new Crypto()),
            $this->mailerFactory(),
            new JsonResponseFactory($psr17, $psr17),
            new ProblemDetailsResponseFactory($psr17, $psr17),
            'http://localhost:5189',
        );

        $request = $psr17->createServerRequest('POST', '/admin/users')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($psr17->createStream($json));

        if ($claims !== null) {
            $request = $request->withAttribute(AdminAuthMiddleware::CLAIMS_ATTRIBUTE, $claims);
        }

        return $handler->handle($request);
    }

    private function useCaseReturningInvite(): CreateInvitedUserUseCaseInterface
    {
        return new class () implements CreateInvitedUserUseCaseInterface {
            public function execute(AuthContext $actor, string $email, string $role): InvitedUser
            {
                $user = new User('usr-1', $actor->organizationId, $email, Role::Editor, '', 'active');

                return new InvitedUser($user, 'raw-token');
            }
        };
    }

    private function settingsRepo(): SmtpSettingsRepositoryInterface
    {
        return new class () implements SmtpSettingsRepositoryInterface {
            public function find(string $organizationId): ?SmtpSettingsRecord
            {
                return null;
            }

            public function save(SmtpSettingsRecord $record): void
            {
            }
        };
    }

    private function mailerFactory(): MailerFactoryInterface
    {
        return new class () implements MailerFactoryInterface {
            public function fromConfig(SmtpConfig $config): MailerInterface
            {
                throw new \LogicException('Mailer must not be used when SMTP is unconfigured.');
            }
        };
    }
}
