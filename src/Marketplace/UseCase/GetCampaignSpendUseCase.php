<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\UseCase;

use NeneServe\Marketplace\Campaign;
use NeneServe\Marketplace\CampaignSpend;
use NeneServe\Marketplace\PricingRuleRepositoryInterface;
use NeneServe\Marketplace\SpendCalculator;
use NeneServe\Measurement\EventStoreInterface;
use NeneServe\Serving\CreativeRepositoryInterface;

/**
 * The single, reproducible derivation of a campaign's net spend (billing §0.6/
 * §3.3): `spent = f(billable_units, pricing_rule_version)` over append-only
 * events. Reused by serve-time budget enforcement and the spend read endpoint,
 * so reporting and accrual never diverge.
 */
final class GetCampaignSpendUseCase
{
    public function __construct(
        private readonly CreativeRepositoryInterface $creatives,
        private readonly EventStoreInterface $events,
        private readonly PricingRuleRepositoryInterface $pricingRules,
    ) {
    }

    public function forCampaign(Campaign $campaign): CampaignSpend
    {
        $rule = $this->pricingRules->findByIdInOrganization($campaign->pricingRuleId, $campaign->organizationId);
        if ($rule === null) {
            throw new MarketplaceValidationException('Campaign references an unknown pricing rule.');
        }

        $creativeIds = $this->creatives->idsByCampaign($campaign->organizationId, $campaign->id);
        $counts = $this->events->billableCountsForCreatives($campaign->organizationId, $creativeIds);

        $spent = SpendCalculator::compute($rule->model, $rule->rateCents, $counts['impressions'], $counts['clicks']);

        return new CampaignSpend(
            $counts['impressions'],
            $counts['clicks'],
            $spent,
            $campaign->budgetCents,
            $rule->version,
        );
    }
}
