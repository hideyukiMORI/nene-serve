<?php

declare(strict_types=1);

namespace NeneServe\Serving\Placements;

use NeneServe\Serving\Placement;

final readonly class ArchivePlacementOutput
{
    public function __construct(
        public Placement $placement,
    ) {
    }
}
