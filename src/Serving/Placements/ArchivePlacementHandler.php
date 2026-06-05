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

        $id = Router::param($request, 'id') ?? '';

        $output = $this->archive->execute(new ArchivePlacementInput($context->userId, $id));

        return $this->response->create([
            'id' => $output->placement->id,
            'status' => $output->placement->status,
            'archived_at' => $output->placement->archivedAt,
        ]);
    }
}
