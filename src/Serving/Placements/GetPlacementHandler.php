<?php

declare(strict_types=1);

namespace NeneServe\Serving\Placements;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneServe\Serving\PlacementRepositoryInterface;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** GET /admin/placements/{id} (operationId `getPlacementById`). Requires `manage_placements`; tenant-scoped. */
final readonly class GetPlacementHandler
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

        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE);
        $id = is_array($parameters) && is_string($parameters['id'] ?? null) ? $parameters['id'] : '';

        $placement = $this->placements->findByIdInOrganization($id, $context->organizationId);

        if ($placement === null) {
            return $this->problemDetails->create($request, 'placement-not-found', 'Placement not found', 404, 'No placement with that id.');
        }

        return $this->response->create($placement->toAdminArray());
    }
}
