<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Serving\PlacementRepositoryInterface;
use NeneServe\Tenant\AuthContext;

/** GET /admin/placements (operationId `listPlacements`). Requires `manage_placements`; tenant-scoped. */
final class ListPlacementsHandler
{
    public function __construct(
        private readonly PlacementRepositoryInterface $placements,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        $placements = array_map(
            static fn ($p) => $p->toAdminArray(),
            $this->placements->listByOrganization($context->organizationId),
        );

        return $this->json->ok(['placements' => $placements]);
    }
}
