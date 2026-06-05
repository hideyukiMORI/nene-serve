<?php

declare(strict_types=1);

namespace NeneServe\Retention\LegalHolds;

final readonly class ReleaseLegalHoldInput
{
    public function __construct(
        public string $actorUserId,
        public string $holdId,
    ) {
    }
}
