<?php

declare(strict_types=1);

namespace NeneServe\Http\Service;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Service\ServiceContext;
use NeneServe\Serving\PlacementRepositoryInterface;

/**
 * GET /api/placements (operationId `listPlacements`, service surface). Requires
 * the `read:placements` scope (enforced by the route guard) and returns only
 * the token's tenant (ADR 0006).
 */
final class ListPlacementsHandler
{
    public function __construct(
        private readonly PlacementRepositoryInterface $placements,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, ServiceContext $context): Response
    {
        $placements = array_map(
            static fn ($p) => [
                'id' => $p->id,
                'public_placement_key' => $p->publicPlacementKey,
                'status' => $p->status,
            ],
            $this->placements->listByOrganization($context->organizationId),
        );

        return $this->json->ok(['placements' => $placements]);
    }
}
