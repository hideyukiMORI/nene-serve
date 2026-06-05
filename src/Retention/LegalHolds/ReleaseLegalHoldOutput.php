<?php

declare(strict_types=1);

namespace NeneServe\Retention\LegalHolds;

use NeneServe\Retention\LegalHold;

final readonly class ReleaseLegalHoldOutput
{
    public function __construct(
        public LegalHold $hold,
    ) {
    }
}
