<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use NeneServe\Serving\Creative;

final readonly class ListCreativesOutput
{
    /** @param list<Creative> $items */
    public function __construct(
        public array $items,
        public int $limit,
        public int $offset,
    ) {
    }
}
