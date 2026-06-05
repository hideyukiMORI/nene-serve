<?php

declare(strict_types=1);

namespace NeneServe\Serving\Placements;

use NeneServe\Serving\UseCase\PlacementNotFoundException;

interface ArchivePlacementUseCaseInterface
{
    /**
     * @throws PlacementNotFoundException
     */
    public function execute(ArchivePlacementInput $input): ArchivePlacementOutput;
}
