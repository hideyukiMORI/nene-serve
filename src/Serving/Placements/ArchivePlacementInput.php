<?php

declare(strict_types=1);

namespace NeneServe\Serving\Placements;

final readonly class ArchivePlacementInput
{
    public function __construct(
        public string $actorUserId,
        public string $placementId,
    ) {
    }
}
