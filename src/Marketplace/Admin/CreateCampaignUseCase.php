<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Marketplace\AdvertiserRepositoryInterface;
use NeneServe\Marketplace\Campaign;
use NeneServe\Marketplace\FundingStatus;
use NeneServe\Marketplace\PdoCampaignRepository;
use NeneServe\Marketplace\PricingRuleRepositoryInterface;
use NeneServe\Marketplace\UseCase\MarketplaceValidationException;
use NeneServe\Money\Money;

final readonly class CreateCampaignUseCase implements CreateCampaignUseCaseInterface
{
    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private AdvertiserRepositoryInterface $advertisers,
        private PricingRuleRepositoryInterface $rules,
        private DatabaseTransactionManagerInterface $transactions,
        private RequestScopedHolder $organizationId,
    ) {
    }

    public function execute(CreateCampaignInput $input): CreateCampaignOutput
    {
        if (trim($input->name) === '') {
            throw new MarketplaceValidationException('name is required.');
        }

        if (!in_array($input->status, ['draft', 'active', 'paused', 'archived'], true)) {
            throw new MarketplaceValidationException('status must be draft|active|paused|archived.');
        }

        $funding = FundingStatus::tryFrom($input->fundingStatus);

        if ($funding === null) {
            throw new MarketplaceValidationException('funding_status must be unfunded|funded.');
        }

        // Validates net integer money (no float, JPY, non-negative).
        $budget = Money::fromCents($input->budgetCents);

        $organizationId = $this->organizationId->get();

        if ($this->advertisers->findByIdInOrganization($input->advertiserId, $organizationId) === null) {
            throw new MarketplaceValidationException('Unknown advertiser.');
        }

        if ($this->rules->findByIdInOrganization($input->pricingRuleId, $organizationId) === null) {
            throw new MarketplaceValidationException('Unknown pricing rule.');
        }

        $campaign = new Campaign(
            'cmp-' . bin2hex(random_bytes(8)),
            $organizationId,
            $input->advertiserId,
            trim($input->name),
            $input->pricingRuleId,
            $budget->cents,
            $input->status,
            $funding,
            $input->pauseOnBudgetExhausted,
        );

        $stored = $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($campaign, $input): Campaign {
                (new PdoCampaignRepository($tx))->save($campaign);
                (new PdoAuditLog($tx))->record(
                    $campaign->organizationId,
                    $input->actorUserId,
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
            },
        );

        return new CreateCampaignOutput($stored);
    }
}
