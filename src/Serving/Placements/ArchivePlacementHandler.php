<?php

declare(strict_types=1);

namespace NeneServe\Serving\Placements;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /admin/placements/{id}/archive (operationId `archivePlacement`). Requires
 * `manage_placements`. Lifecycle "delete" = archive tombstone (ADR 0022).
 */
final readonly class ArchivePlacementHandler
{
    public function __construct(
        private ArchivePlacementUseCaseInterface $archive,
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

        $placement = $this->archive->execute($context, $id);

        return $this->response->create([
            'id' => $placement->id,
            'status' => $placement->status,
            'archived_at' => $placement->archivedAt,
        ]);
    }
}
