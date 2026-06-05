<?php

declare(strict_types=1);

namespace NeneServe\Tests\Marketplace;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Marketplace\Admin\ListAdvertisersHandler;
use NeneServe\Marketplace\Advertiser;
use NeneServe\Marketplace\AdvertiserRepositoryInterface;
use NeneServe\Tenant\Auth\AdminAuthMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class ListAdvertisersHandlerTest extends TestCase
{
    public function testListsAdvertisers(): void
    {
        $response = $this->handle(['org' => 'org-acme', 'role' => 'org_admin', 'sub' => 'u-1']);

        self::assertSame(200, $response->getStatusCode());
        /** @var array{advertisers: list<array{name: string}>} $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertSame('Acme Media', $body['advertisers'][0]['name']);
    }

    public function testRejectsRequestWithoutClaims(): void
    {
        self::assertSame(401, $this->handle(null)->getStatusCode());
    }

    /**
     * @param array<string, mixed>|null $claims
     */
    private function handle(?array $claims): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $handler = new ListAdvertisersHandler(
            $this->repo(),
            new JsonResponseFactory($psr17, $psr17),
            new ProblemDetailsResponseFactory($psr17, $psr17),
        );

        $request = $psr17->createServerRequest('GET', '/admin/advertisers');

        if ($claims !== null) {
            $request = $request->withAttribute(AdminAuthMiddleware::CLAIMS_ATTRIBUTE, $claims);
        }

        return $handler->handle($request);
    }

    private function repo(): AdvertiserRepositoryInterface
    {
        return new class () implements AdvertiserRepositoryInterface {
            public function findByIdInOrganization(string $id, string $organizationId): ?Advertiser
            {
                return null;
            }

            public function listByOrganization(string $organizationId): array
            {
                return [new Advertiser('adv-1', $organizationId, 'Acme Media', 'active')];
            }

            public function save(Advertiser $advertiser): void
            {
            }
        };
    }
}
