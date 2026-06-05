<?php

declare(strict_types=1);

namespace NeneServe\Tests\Serving\Placements;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationException;
use NeneServe\Serving\Placement;
use NeneServe\Serving\PlacementRepositoryInterface;
use NeneServe\Serving\Placements\ArchivePlacementHandler;
use NeneServe\Serving\Placements\ArchivePlacementUseCaseInterface;
use NeneServe\Serving\Placements\CreatePlacementHandler;
use NeneServe\Serving\Placements\CreatePlacementUseCaseInterface;
use NeneServe\Serving\Placements\GetPlacementHandler;
use NeneServe\Serving\Placements\ListPlacementsHandler;
use NeneServe\Tenant\Auth\AdminAuthMiddleware;
use NeneServe\Tenant\AuthContext;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class PlacementsHandlerTest extends TestCase
{
    private const CLAIMS = ['org' => 'org-acme', 'role' => 'org_admin', 'sub' => 'u-1'];

    public function testListReturnsTenantPlacements(): void
    {
        $psr17 = new Psr17Factory();
        $handler = new ListPlacementsHandler($this->repo($this->placement()), $this->json($psr17), $this->problem($psr17));

        $response = $handler->handle($this->request($psr17, 'GET', '/admin/placements'));

        self::assertSame(200, $response->getStatusCode());
        /** @var array{placements: list<array{id: string}>} $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertSame('plc-1', $body['placements'][0]['id']);
    }

    public function testGetReturns404WhenMissing(): void
    {
        $psr17 = new Psr17Factory();
        $handler = new GetPlacementHandler($this->repo(null), $this->json($psr17), $this->problem($psr17));

        $request = $this->request($psr17, 'GET', '/admin/placements/x')
            ->withAttribute(Router::PARAMETERS_ATTRIBUTE, ['id' => 'x']);

        self::assertSame(404, $handler->handle($request)->getStatusCode());
    }

    public function testCreateRejectsMissingKey(): void
    {
        $psr17 = new Psr17Factory();
        $handler = new CreatePlacementHandler($this->createUseCase(), $this->json($psr17), $this->problem($psr17));

        $this->expectException(ValidationException::class);
        $handler->handle($this->jsonRequest($psr17, '{}'));
    }

    public function testCreateReturns201(): void
    {
        $psr17 = new Psr17Factory();
        $handler = new CreatePlacementHandler($this->createUseCase(), $this->json($psr17), $this->problem($psr17));

        $response = $handler->handle($this->jsonRequest($psr17, '{"public_placement_key":"pk_home"}'));

        self::assertSame(201, $response->getStatusCode());
    }

    public function testArchiveReturns200(): void
    {
        $psr17 = new Psr17Factory();
        $handler = new ArchivePlacementHandler($this->archiveUseCase(), $this->json($psr17), $this->problem($psr17));

        $request = $this->request($psr17, 'POST', '/admin/placements/plc-1/archive')
            ->withAttribute(Router::PARAMETERS_ATTRIBUTE, ['id' => 'plc-1']);

        self::assertSame(200, $handler->handle($request)->getStatusCode());
    }

    private function placement(): Placement
    {
        return new Placement('plc-1', 'org-acme', 'pk_home', [], 'active', null);
    }

    private function request(Psr17Factory $psr17, string $method, string $path): ServerRequestInterface
    {
        return $psr17->createServerRequest($method, $path)->withAttribute(AdminAuthMiddleware::CLAIMS_ATTRIBUTE, self::CLAIMS);
    }

    private function jsonRequest(Psr17Factory $psr17, string $json): ServerRequestInterface
    {
        return $this->request($psr17, 'POST', '/admin/placements')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($psr17->createStream($json));
    }

    private function json(Psr17Factory $psr17): JsonResponseFactory
    {
        return new JsonResponseFactory($psr17, $psr17);
    }

    private function problem(Psr17Factory $psr17): ProblemDetailsResponseFactory
    {
        return new ProblemDetailsResponseFactory($psr17, $psr17);
    }

    private function repo(?Placement $placement): PlacementRepositoryInterface
    {
        return new class ($placement) implements PlacementRepositoryInterface {
            public function __construct(private readonly ?Placement $placement)
            {
            }

            public function findByPublicKey(string $publicPlacementKey): ?Placement
            {
                return $this->placement;
            }

            public function findByIdInOrganization(string $id, string $organizationId): ?Placement
            {
                return $this->placement;
            }

            public function listByOrganization(string $organizationId): array
            {
                return $this->placement === null ? [] : [$this->placement];
            }

            public function save(Placement $placement): void
            {
            }

            public function archive(string $id, string $organizationId, string $at): void
            {
            }
        };
    }

    private function createUseCase(): CreatePlacementUseCaseInterface
    {
        return new class () implements CreatePlacementUseCaseInterface {
            public function execute(AuthContext $actor, string $publicPlacementKey, array $allowedOrigins, ?string $defaultCreativeId = null, string $status = 'draft'): Placement
            {
                return new Placement('plc-new', $actor->organizationId, $publicPlacementKey, $allowedOrigins, $status, $defaultCreativeId);
            }
        };
    }

    private function archiveUseCase(): ArchivePlacementUseCaseInterface
    {
        return new class () implements ArchivePlacementUseCaseInterface {
            public function execute(AuthContext $actor, string $placementId): Placement
            {
                return new Placement($placementId, $actor->organizationId, 'pk_home', [], 'archived', null, true, null, gmdate('c'));
            }
        };
    }
}
