<?php

declare(strict_types=1);

namespace NeneServe\Tests\Serving\Placements;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationException;
use NeneServe\Serving\Placement;
use NeneServe\Serving\Placements\ArchivePlacementHandler;
use NeneServe\Serving\Placements\ArchivePlacementInput;
use NeneServe\Serving\Placements\ArchivePlacementOutput;
use NeneServe\Serving\Placements\ArchivePlacementUseCaseInterface;
use NeneServe\Serving\Placements\CreatePlacementHandler;
use NeneServe\Serving\Placements\CreatePlacementInput;
use NeneServe\Serving\Placements\CreatePlacementOutput;
use NeneServe\Serving\Placements\CreatePlacementUseCaseInterface;
use NeneServe\Serving\Placements\GetPlacementByIdInput;
use NeneServe\Serving\Placements\GetPlacementByIdOutput;
use NeneServe\Serving\Placements\GetPlacementByIdUseCaseInterface;
use NeneServe\Serving\Placements\GetPlacementHandler;
use NeneServe\Serving\Placements\ListPlacementsHandler;
use NeneServe\Serving\Placements\ListPlacementsInput;
use NeneServe\Serving\Placements\ListPlacementsOutput;
use NeneServe\Serving\Placements\ListPlacementsUseCaseInterface;
use NeneServe\Serving\UseCase\PlacementNotFoundException;
use NeneServe\Tenant\Auth\AdminAuthMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class PlacementsHandlerTest extends TestCase
{
    private const CLAIMS = ['org' => 'org-acme', 'role' => 'org_admin', 'sub' => 'u-1'];

    public function testListReturnsPaginatedEnvelope(): void
    {
        $psr17 = new Psr17Factory();
        $useCase = new class () implements ListPlacementsUseCaseInterface {
            public function execute(ListPlacementsInput $input): ListPlacementsOutput
            {
                return new ListPlacementsOutput([new Placement('plc-1', 'org-acme', 'pk_home', [], 'active', null)], $input->limit, $input->offset);
            }
        };
        $handler = new ListPlacementsHandler($useCase, $this->json($psr17));

        $response = $handler->handle($this->request($psr17, 'GET', '/admin/placements'));

        self::assertSame(200, $response->getStatusCode());
        /** @var array{items: list<array{id: string}>, limit: int, offset: int} $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertSame('plc-1', $body['items'][0]['id']);
        self::assertSame(20, $body['limit']);
        self::assertSame(0, $body['offset']);
    }

    public function testGetThrowsWhenMissing(): void
    {
        $psr17 = new Psr17Factory();
        $useCase = new class () implements GetPlacementByIdUseCaseInterface {
            public function execute(GetPlacementByIdInput $input): GetPlacementByIdOutput
            {
                throw new PlacementNotFoundException();
            }
        };
        $handler = new GetPlacementHandler($useCase, $this->json($psr17));

        $this->expectException(PlacementNotFoundException::class);
        $handler->handle($this->request($psr17, 'GET', '/admin/placements/x')->withAttribute(Router::PARAMETERS_ATTRIBUTE, ['id' => 'x']));
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
        $useCase = new class () implements ArchivePlacementUseCaseInterface {
            public function execute(ArchivePlacementInput $input): ArchivePlacementOutput
            {
                return new ArchivePlacementOutput(new Placement($input->placementId, 'org-acme', 'pk_home', [], 'archived', null, true, null, gmdate('c')));
            }
        };
        $handler = new ArchivePlacementHandler($useCase, $this->json($psr17), $this->problem($psr17));

        $request = $this->request($psr17, 'POST', '/admin/placements/plc-1/archive')
            ->withAttribute(Router::PARAMETERS_ATTRIBUTE, ['id' => 'plc-1']);

        self::assertSame(200, $handler->handle($request)->getStatusCode());
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

    private function createUseCase(): CreatePlacementUseCaseInterface
    {
        return new class () implements CreatePlacementUseCaseInterface {
            public function execute(CreatePlacementInput $input): CreatePlacementOutput
            {
                return new CreatePlacementOutput(new Placement('plc-new', 'org-acme', $input->publicPlacementKey, $input->allowedOrigins, $input->status, $input->defaultCreativeId));
            }
        };
    }
}
