<?php

declare(strict_types=1);

namespace NeneServe\Tests\Auth;

use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationException;
use NeneServe\Auth\LoginHandler;
use NeneServe\Auth\LoginInput;
use NeneServe\Auth\LoginOutput;
use NeneServe\Auth\LoginUseCaseInterface;
use NeneServe\Tenant\Resolution\OrgResolverMiddleware;
use NeneServe\Tenant\UseCase\AuthenticationFailedException;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

final class LoginHandlerTest extends TestCase
{
    public function testReturnsTokenAndUserOnSuccess(): void
    {
        $useCase = new class () implements LoginUseCaseInterface {
            public function execute(LoginInput $input): LoginOutput
            {
                return new LoginOutput('signed.jwt.token', [
                    'id' => 'u-1',
                    'organization_id' => 'org-acme',
                    'email' => $input->email,
                    'role' => 'org_admin',
                ]);
            }
        };

        $response = $this->handle($useCase, '{"organization":"acme","email":"admin@acme.test","password":"secret"}');

        self::assertSame(200, $response->getStatusCode());

        /** @var array{token: string, user: array{id: string}} $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertSame('signed.jwt.token', $body['token']);
        self::assertSame('u-1', $body['user']['id']);
    }

    public function testMissingFieldsThrowValidationException(): void
    {
        $useCase = $this->neverCalledUseCase();

        $this->expectException(ValidationException::class);
        $this->handle($useCase, '{}');
    }

    public function testAuthenticationFailurePropagatesForTheExceptionHandler(): void
    {
        $useCase = new class () implements LoginUseCaseInterface {
            public function execute(LoginInput $input): LoginOutput
            {
                throw new AuthenticationFailedException();
            }
        };

        $this->expectException(AuthenticationFailedException::class);
        $this->handle($useCase, '{"organization":"acme","email":"x@y.test","password":"nope"}');
    }

    public function testResolvedTenantOverridesBodyOrganization(): void
    {
        $useCase = new class () implements LoginUseCaseInterface {
            public ?string $seenOrganization = null;

            public function execute(LoginInput $input): LoginOutput
            {
                $this->seenOrganization = $input->organization;

                return new LoginOutput('t', ['id' => 'u-1', 'organization_id' => 'org-acme', 'email' => $input->email, 'role' => 'org_admin']);
            }
        };

        // URL mode resolved 'acme'; a body trying another org is ignored.
        $this->handle($useCase, '{"organization":"evil","email":"a@acme.test","password":"secret"}', 'acme');

        self::assertSame('acme', $useCase->seenOrganization);
    }

    public function testResolvedTenantSatisfiesMissingBodyOrganization(): void
    {
        $useCase = new class () implements LoginUseCaseInterface {
            public ?string $seenOrganization = null;

            public function execute(LoginInput $input): LoginOutput
            {
                $this->seenOrganization = $input->organization;

                return new LoginOutput('t', ['id' => 'u-1', 'organization_id' => 'org-acme', 'email' => $input->email, 'role' => 'org_admin']);
            }
        };

        // In URL modes the SPA omits the org field; the resolved tenant fills it.
        $this->handle($useCase, '{"email":"a@acme.test","password":"secret"}', 'acme');

        self::assertSame('acme', $useCase->seenOrganization);
    }

    private function handle(LoginUseCaseInterface $useCase, string $json, ?string $resolvedSlug = null): \Psr\Http\Message\ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $handler = new LoginHandler($useCase, new JsonResponseFactory($psr17, $psr17));

        $request = $psr17->createServerRequest('POST', '/admin/login')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($psr17->createStream($json));

        if ($resolvedSlug !== null) {
            $request = $request->withAttribute(OrgResolverMiddleware::RESOLVED_ORG_SLUG_ATTRIBUTE, $resolvedSlug);
        }

        return $handler->handle($request);
    }

    private function neverCalledUseCase(): LoginUseCaseInterface
    {
        return new class () implements LoginUseCaseInterface {
            public function execute(LoginInput $input): LoginOutput
            {
                throw new \LogicException('Use case must not be reached.');
            }
        };
    }
}
