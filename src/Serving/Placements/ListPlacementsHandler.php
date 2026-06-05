<?php

declare(strict_types=1);

namespace NeneServe\Serving\Placements;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Serving\PlacementRepositoryInterface;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** GET /admin/placements (operationId `listPlacements`). Requires `manage_placements`; tenant-scoped. */
final readonly class ListPlacementsHandler
{
    public function __construct(
        private PlacementRepositoryInterface $placements,
        private JsonResponseFactory $response,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $context = AuthContextResolver::fromRequest($request);

        if ($context === null) {
            return $this->problemDetails->create($request, 'unauthorized', 'Unauthorized', 401, 'Authentication is required.');
        }

        $placements = array_map(
            static fn ($placement) => $placement->toAdminArray(),
            $this->placements->listByOrganization($context->organizationId),
        );

        return $this->response->create(['placements' => $placements]);
    }
}
