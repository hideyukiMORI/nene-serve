<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

final readonly class CreatePricingRuleInput
{
    public function __construct(
        public string $actorUserId,
        public string $name,
        public string $model,
        public int $rateCents,
    ) {
    }
}
