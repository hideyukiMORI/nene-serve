<?php

declare(strict_types=1);

namespace NeneServe\Serving\Placements;

interface ListPlacementsUseCaseInterface
{
    public function execute(ListPlacementsInput $input): ListPlacementsOutput;
}
