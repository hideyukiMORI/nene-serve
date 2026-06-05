<?php

declare(strict_types=1);

namespace NeneServe\Serving\Placements;

use NeneServe\Serving\UseCase\PlacementNotFoundException;

interface GetPlacementByIdUseCaseInterface
{
    /**
     * @throws PlacementNotFoundException
     */
    public function execute(GetPlacementByIdInput $input): GetPlacementByIdOutput;
}
