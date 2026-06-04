<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Serving\UseCase\ArchivePlacementUseCase;
use NeneServe\Serving\UseCase\PlacementNotFoundException;
use NeneServe\Tenant\AuthContext;

/**
 * POST /admin/placements/{id}/archive (operationId `archivePlacement`). Requires
 * `manage_placements`. Lifecycle "delete" = archive tombstone (ADR 0022); the
 * row is never physically removed.
 */
final class ArchivePlacementHandler
{
    public function __construct(
        private readonly ArchivePlacementUseCase $archive,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        try {
            $placement = $this->archive->execute($context, (string) $request->param('id'));
        } catch (PlacementNotFoundException) {
            return $this->json->problem(404, 'placement-not-found', 'Placement not found');
        }

        return $this->json->ok([
            'id' => $placement->id,
            'status' => $placement->status,
            'archived_at' => $placement->archivedAt,
        ]);
    }
}
