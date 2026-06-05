<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

final readonly class ListPricingRulesInput
{
    public function __construct(
        public int $limit = 20,
        public int $offset = 0,
    ) {
    }
}
