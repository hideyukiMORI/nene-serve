<?php

declare(strict_types=1);

namespace NeneServe\Tests\Tenant\Account;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Tenant\Account\CurrentUserHandler;
use NeneServe\Tenant\Auth\AdminAuthMiddleware;
use NeneServe\Tenant\Role;
use NeneServe\Tenant\User;
use NeneServe\Tenant\UserRepositoryInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class CurrentUserHandlerTest extends TestCase
{
    public function testReturnsPrincipalAndCapabilitiesForAuthenticatedRequest(): void
    {
        $user = new User('u-1', 'org-acme', 'admin@acme.test', Role::OrgAdmin, password_hash('x', PASSWORD_DEFAULT));

        $response = $this->handle($this->repositoryReturning($user), [
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
        $response = $this->handle($this->repositoryReturning(null), null);

        self::assertSame(401, $response->getStatusCode());
    }

    /**
     * @param array<string, mixed>|null $claims
     */
    private function handle(UserRepositoryInterface $users, ?array $claims): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $handler = new CurrentUserHandler(
            $users,
            new JsonResponseFactory($psr17, $psr17),
            new ProblemDetailsResponseFactory($psr17, $psr17),
        );

        $request = $psr17->createServerRequest('GET', '/admin/me');

        if ($claims !== null) {
            $request = $request->withAttribute(AdminAuthMiddleware::CLAIMS_ATTRIBUTE, $claims);
        }

        return $handler->handle($request);
    }

    private function repositoryReturning(?User $user): UserRepositoryInterface
    {
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

            public function listByOrganization(string $organizationId): array
            {
                return [];
            }

            public function findByIdAcrossTenants(string $userId): ?User
            {
                return null;
            }

            public function listAll(): array
            {
                return [];
            }
        };
    }
}
