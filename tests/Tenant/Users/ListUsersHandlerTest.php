<?php

declare(strict_types=1);

namespace NeneServe\Tests\Tenant\Users;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Tenant\Auth\AdminAuthMiddleware;
use NeneServe\Tenant\Role;
use NeneServe\Tenant\User;
use NeneServe\Tenant\Users\ListUsersHandler;
use NeneServe\Tenant\Users\ListUsersInput;
use NeneServe\Tenant\Users\ListUsersOutput;
use NeneServe\Tenant\Users\ListUsersUseCaseInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class ListUsersHandlerTest extends TestCase
{
    public function testListsUsersInPaginatedEnvelope(): void
    {
        $response = $this->handle(['org' => 'org-acme', 'role' => 'org_admin', 'sub' => 'u-1']);

        self::assertSame(200, $response->getStatusCode());

        /** @var array{items: list<array{email: string}>, limit: int} $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertCount(2, $body['items']);
        self::assertSame('admin@acme.test', $body['items'][0]['email']);
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
        $useCase = new class () implements ListUsersUseCaseInterface {
            public function execute(ListUsersInput $input): ListUsersOutput
            {
                return new ListUsersOutput([
                    new User('u-1', 'org-acme', 'admin@acme.test', Role::OrgAdmin, 'h'),
                    new User('u-2', 'org-acme', 'analyst@acme.test', Role::Analyst, 'h'),
                ], $input->limit, $input->offset);
            }
        };
        $handler = new ListUsersHandler($useCase, new JsonResponseFactory($psr17, $psr17), new ProblemDetailsResponseFactory($psr17, $psr17));

        $request = $psr17->createServerRequest('GET', '/admin/users');

        if ($claims !== null) {
            $request = $request->withAttribute(AdminAuthMiddleware::CLAIMS_ATTRIBUTE, $claims);
        }

        return $handler->handle($request);
    }
}
