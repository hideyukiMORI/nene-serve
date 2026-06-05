<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Marketplace\Campaign;
use NeneServe\Marketplace\FundingStatus;
use NeneServe\Marketplace\PdoAdvertiserRepository;
use NeneServe\Marketplace\PdoCampaignRepository;
use NeneServe\Marketplace\PdoPricingRuleRepository;
use NeneServe\Marketplace\UseCase\MarketplaceValidationException;
use NeneServe\Money\Money;
use NeneServe\Tenant\AuthContext;

final readonly class CreateCampaignUseCase implements CreateCampaignUseCaseInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private DatabaseTransactionManagerInterface $transactions,
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

        if ((new PdoAdvertiserRepository($this->query))->findByIdInOrganization($advertiserId, $actor->organizationId) === null) {
            throw new MarketplaceValidationException('Unknown advertiser.');
        }

        if ((new PdoPricingRuleRepository($this->query))->findByIdInOrganization($pricingRuleId, $actor->organizationId) === null) {
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

        return $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($campaign, $actor): Campaign {
                (new PdoCampaignRepository($tx))->save($campaign);
                (new PdoAuditLog($tx))->record(
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
            },
        );
    }
}
