<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

final readonly class ListCreativesInput
{
    public function __construct(
        public int $limit = 20,
        public int $offset = 0,
    ) {
    }
}
