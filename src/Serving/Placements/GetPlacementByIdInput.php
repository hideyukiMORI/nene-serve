<?php

declare(strict_types=1);

namespace NeneServe\Serving\Placements;

final readonly class GetPlacementByIdInput
{
    public function __construct(
        public string $id,
    ) {
    }
}
