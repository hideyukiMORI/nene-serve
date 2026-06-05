<?php

declare(strict_types=1);

namespace NeneServe\Serving\Placements;

final readonly class ListPlacementsInput
{
    public function __construct(
        public int $limit = 20,
        public int $offset = 0,
    ) {
    }
}
