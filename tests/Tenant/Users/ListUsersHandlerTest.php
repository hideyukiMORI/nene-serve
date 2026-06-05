<?php

declare(strict_types=1);

namespace NeneServe\Tests\Tenant\Users;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Tenant\Auth\AdminAuthMiddleware;
use NeneServe\Tenant\Role;
use NeneServe\Tenant\UseCase\ListUsersUseCase;
use NeneServe\Tenant\User;
use NeneServe\Tenant\UserRepositoryInterface;
use NeneServe\Tenant\Users\ListUsersHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class ListUsersHandlerTest extends TestCase
{
    public function testListsUsersInTheCallersTenant(): void
    {
        $response = $this->handle(['org' => 'org-acme', 'role' => 'org_admin', 'sub' => 'u-1']);

        self::assertSame(200, $response->getStatusCode());

        /** @var array{users: list<array{email: string}>} $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertCount(2, $body['users']);
        self::assertSame('admin@acme.test', $body['users'][0]['email']);
    }

    public function testRejectsRequestWithoutClaims(): void
    {
        $response = $this->handle(null);

        self::assertSame(401, $response->getStatusCode());
    }

    /**
     * @param array<string, mixed>|null $claims
     */
    private function handle(?array $claims): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $handler = new ListUsersHandler(
            new ListUsersUseCase($this->repository()),
            new JsonResponseFactory($psr17, $psr17),
            new ProblemDetailsResponseFactory($psr17, $psr17),
        );

        $request = $psr17->createServerRequest('GET', '/admin/users');

        if ($claims !== null) {
            $request = $request->withAttribute(AdminAuthMiddleware::CLAIMS_ATTRIBUTE, $claims);
        }

        return $handler->handle($request);
    }

    private function repository(): UserRepositoryInterface
    {
        return new class () implements UserRepositoryInterface {
            public function findByIdInOrganization(string $userId, string $organizationId): ?User
            {
                return null;
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
                return [
                    new User('u-1', $organizationId, 'admin@acme.test', Role::OrgAdmin, 'h'),
                    new User('u-2', $organizationId, 'analyst@acme.test', Role::Analyst, 'h'),
                ];
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
