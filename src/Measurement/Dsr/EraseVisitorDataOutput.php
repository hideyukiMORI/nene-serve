<?php

declare(strict_types=1);

namespace NeneServe\Measurement\Dsr;

final readonly class EraseVisitorDataOutput
{
    public function __construct(
        public int $tombstoned,
    ) {
    }
}
