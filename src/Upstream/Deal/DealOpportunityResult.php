<?php

declare(strict_types=1);

namespace NeneServe\Upstream\Deal;

/** Result of creating an opportunity in NeNe Deal (sibling-products map). */
final class DealOpportunityResult
{
    public function __construct(
        public readonly string $opportunityId,
        public readonly string $status,
    ) {
    }
}
