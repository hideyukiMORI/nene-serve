<?php

declare(strict_types=1);

namespace NeneServe\Tests\Tenant\Account;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Tenant\Account\CurrentUserHandler;
use NeneServe\Tenant\Account\GetCurrentUserInput;
use NeneServe\Tenant\Account\GetCurrentUserOutput;
use NeneServe\Tenant\Account\GetCurrentUserUseCaseInterface;
use NeneServe\Tenant\Auth\AdminAuthMiddleware;
use NeneServe\Tenant\Auth\AuthContextRequiredException;
use NeneServe\Tenant\Role;
use NeneServe\Tenant\User;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class CurrentUserHandlerTest extends TestCase
{
    public function testReturnsPrincipalAndCapabilitiesForAuthenticatedRequest(): void
    {
        $user = new User('u-1', 'org-acme', 'admin@acme.test', Role::OrgAdmin, password_hash('x', PASSWORD_DEFAULT));

        $response = $this->handle($this->useCaseReturning($user), [
            'sub' => 'u-1',
            'org' => 'org-acme',
            'role' => 'org_admin',
        ]);

        self::assertSame(200, $response->getStatusCode());

        /** @var array{user: array{id: string}, capabilities: list<string>} $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertSame('u-1', $body['user']['id']);
        self::assertContains('manage_users', $body['capabilities']);
    }

    public function testRejectsRequestWithoutClaims(): void
    {
        $this->expectException(AuthContextRequiredException::class);
        $this->handle($this->useCaseReturning(null), null);
    }

    /**
     * @param array<string, mixed>|null $claims
     */
    private function handle(GetCurrentUserUseCaseInterface $useCase, ?array $claims): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $handler = new CurrentUserHandler(
            $useCase,
            new JsonResponseFactory($psr17, $psr17),
            new ProblemDetailsResponseFactory($psr17, $psr17),
        );

        $request = $psr17->createServerRequest('GET', '/admin/me');

        if ($claims !== null) {
            $request = $request->withAttribute(AdminAuthMiddleware::CLAIMS_ATTRIBUTE, $claims);
        }

        return $handler->handle($request);
    }

    private function useCaseReturning(?User $user): GetCurrentUserUseCaseInterface
    {
        return new class ($user) implements GetCurrentUserUseCaseInterface {
            public function __construct(private readonly ?User $user)
            {
            }

            public function execute(GetCurrentUserInput $input): GetCurrentUserOutput
            {
                return new GetCurrentUserOutput($this->user);
            }
        };
    }
}
