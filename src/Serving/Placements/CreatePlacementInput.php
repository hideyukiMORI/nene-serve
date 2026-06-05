<?php

declare(strict_types=1);

namespace NeneServe\Serving\Placements;

final readonly class CreatePlacementInput
{
    /**
     * @param list<string> $allowedOrigins
     */
    public function __construct(
        public string $actorUserId,
        public string $publicPlacementKey,
        public array $allowedOrigins,
        public ?string $defaultCreativeId = null,
        public string $status = 'draft',
    ) {
    }
}
