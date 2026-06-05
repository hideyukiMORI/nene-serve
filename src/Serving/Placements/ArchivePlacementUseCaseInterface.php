<?php

declare(strict_types=1);

namespace NeneServe\Serving\Placements;

use NeneServe\Serving\Placement;
use NeneServe\Serving\UseCase\PlacementNotFoundException;
use NeneServe\Tenant\AuthContext;

interface ArchivePlacementUseCaseInterface
{
    /**
     * @throws PlacementNotFoundException
     */
    public function execute(AuthContext $actor, string $placementId): Placement;
}
