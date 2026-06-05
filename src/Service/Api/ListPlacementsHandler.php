<?php

declare(strict_types=1);

namespace NeneServe\Service\Api;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Service\Auth\ServiceContextResolver;
use NeneServe\Serving\PdoPlacementRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /api/placements (operationId `listPlacements`, service surface). Requires
 * the `read:placements` scope (enforced by {@see \NeneServe\Service\Auth\ScopeMiddleware})
 * and returns only the token's tenant (ADR 0006).
 */
final readonly class ListPlacementsHandler
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private JsonResponseFactory $response,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $context = ServiceContextResolver::fromRequest($request);

        if ($context === null) {
            return $this->problemDetails->create($request, 'unauthorized', 'Unauthorized', 401, 'A service token is required.');
        }

        $placements = array_map(
            static fn ($p): array => [
                'id' => $p->id,
                'public_placement_key' => $p->publicPlacementKey,
                'status' => $p->status,
            ],
            (new PdoPlacementRepository($this->query))->listByOrganization($context->organizationId),
        );

        return $this->response->create(['placements' => $placements]);
    }
}
