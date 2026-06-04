<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\UseCase;

use NeneServe\Audit\AuditLogInterface;
use NeneServe\Marketplace\AdvertiserRepositoryInterface;
use NeneServe\Marketplace\Campaign;
use NeneServe\Marketplace\CampaignRepositoryInterface;
use NeneServe\Marketplace\FundingStatus;
use NeneServe\Marketplace\PricingRuleRepositoryInterface;
use NeneServe\Money\Money;
use NeneServe\Support\TransactionManagerInterface;
use NeneServe\Tenant\AuthContext;

final class CreateCampaignUseCase
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaigns,
        private readonly AdvertiserRepositoryInterface $advertisers,
        private readonly PricingRuleRepositoryInterface $pricingRules,
        private readonly AuditLogInterface $audit,
        private readonly TransactionManagerInterface $tx,
    ) {
    }

    public function execute(
        AuthContext $actor,
        string $advertiserId,
        string $name,
        string $pricingRuleId,
        int $budgetCents,
        bool $pauseOnBudgetExhausted = true,
        string $status = 'draft',
        string $fundingStatus = 'unfunded',
    ): Campaign {
        if (trim($name) === '') {
            throw new MarketplaceValidationException('name is required.');
        }
        if (!in_array($status, ['draft', 'active', 'paused', 'archived'], true)) {
            throw new MarketplaceValidationException('status must be draft|active|paused|archived.');
        }
        $funding = FundingStatus::tryFrom($fundingStatus);
        if ($funding === null) {
            throw new MarketplaceValidationException('funding_status must be unfunded|funded.');
        }
        // Validates net integer money (no float, JPY, non-negative).
        $budget = Money::fromCents($budgetCents);

        if ($this->advertisers->findByIdInOrganization($advertiserId, $actor->organizationId) === null) {
            throw new MarketplaceValidationException('Unknown advertiser.');
        }
        if ($this->pricingRules->findByIdInOrganization($pricingRuleId, $actor->organizationId) === null) {
            throw new MarketplaceValidationException('Unknown pricing rule.');
        }

        $campaign = new Campaign(
            'cmp-' . bin2hex(random_bytes(8)),
            $actor->organizationId,
            $advertiserId,
            trim($name),
            $pricingRuleId,
            $budget->cents,
            $status,
            $funding,
            $pauseOnBudgetExhausted,
        );

        return $this->tx->transactional(function () use ($campaign, $actor): Campaign {
            $this->campaigns->save($campaign);
            $this->audit->record(
                $actor->organizationId,
                $actor->userId,
                'campaign.created',
                'campaign',
                $campaign->id,
                ['after' => [
                    'advertiser_id' => $campaign->advertiserId,
                    'budget_cents' => $campaign->budgetCents,
                    'pricing_rule_id' => $campaign->pricingRuleId,
                ]],
            );

            return $campaign;
        });
    }
}
