<?php

declare(strict_types=1);

namespace NeneServe\Serving\Placements;

use NeneServe\Serving\UseCase\CreativeValidationException;

interface CreatePlacementUseCaseInterface
{
    /**
     * @throws CreativeValidationException
     */
    public function execute(CreatePlacementInput $input): CreatePlacementOutput;
}
