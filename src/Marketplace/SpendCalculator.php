<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

/**
 * Derives net spend from billable units and a versioned pricing rule — the
 * **single, reproducible** money function (billing §3.3): every figure is
 * `amount = f(billable_units, pricing_rule_version)`. Integer cents only; CPM
 * rounds **down** (floor) so spend never exceeds what units justify.
 */
final class SpendCalculator
{
    public static function compute(PricingModel $model, int $rateCents, int $impressions, int $clicks): int
    {
        return match ($model) {
            PricingModel::Cpm => intdiv($impressions * $rateCents, 1000), // rate per 1000 impressions, floor
            PricingModel::Cpc => $clicks * $rateCents,
            PricingModel::Flat => $rateCents,
        };
    }
}
