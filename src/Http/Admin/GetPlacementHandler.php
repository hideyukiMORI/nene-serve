<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Serving\PlacementRepositoryInterface;
use NeneServe\Tenant\AuthContext;

/** GET /admin/placements/{id} (operationId `getPlacementById`). Requires `manage_placements`; tenant-scoped. */
final class GetPlacementHandler
{
    public function __construct(
        private readonly PlacementRepositoryInterface $placements,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        $placement = $this->placements->findByIdInOrganization(
            (string) $request->param('id'),
            $context->organizationId,
        );
        if ($placement === null) {
            return $this->json->problem(404, 'placement-not-found', 'Placement not found');
        }

        return $this->json->ok($placement->toAdminArray());
    }
}
