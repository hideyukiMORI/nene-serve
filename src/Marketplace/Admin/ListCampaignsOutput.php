<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use NeneServe\Marketplace\Campaign;

final readonly class ListCampaignsOutput
{
    /** @param list<Campaign> $items */
    public function __construct(
        public array $items,
        public int $limit,
        public int $offset,
    ) {
    }
}
