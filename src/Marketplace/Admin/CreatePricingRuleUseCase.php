<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Marketplace\PdoPricingRuleRepository;
use NeneServe\Marketplace\PricingModel;
use NeneServe\Marketplace\PricingRule;
use NeneServe\Marketplace\PricingRuleRepositoryInterface;
use NeneServe\Marketplace\UseCase\MarketplaceValidationException;
use NeneServe\Money\Money;
use NeneServe\Support\Id;

/**
 * Pricing rules are immutable: a change is a new version row (billing-and-
 * accounting §3). Committed atomically via the NENE2 transaction pattern.
 */
final readonly class CreatePricingRuleUseCase implements CreatePricingRuleUseCaseInterface
{
    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private PricingRuleRepositoryInterface $rules,
        private DatabaseTransactionManagerInterface $transactions,
        private RequestScopedHolder $organizationId,
    ) {
    }

    public function execute(CreatePricingRuleInput $input): CreatePricingRuleOutput
    {
        if (trim($input->name) === '') {
            throw new MarketplaceValidationException('name is required.');
        }

        $pricingModel = PricingModel::tryFrom($input->model);

        if ($pricingModel === null) {
            throw new MarketplaceValidationException('pricing_model must be one of: cpm, cpc, flat.');
        }

        // Validates integer / JPY / non-negative / no-tax invariants.
        $rate = Money::fromCents($input->rateCents);

        $organizationId = $this->organizationId->get();
        $version = $this->rules->currentVersion($organizationId, trim($input->name)) + 1;

        $rule = new PricingRule(
            Id::generate('pr'),
            $organizationId,
            trim($input->name),
            $pricingModel,
            $rate->cents,
            $version,
            gmdate('c'),
        );

        $stored = $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($rule, $input): PricingRule {
                (new PdoPricingRuleRepository($tx))->save($rule);
                (new PdoAuditLog($tx))->record(
                    $rule->organizationId,
                    $input->actorUserId,
                    'pricing_rule.created',
                    'pricing_rule',
                    $rule->id,
                    ['after' => [
                        'name' => $rule->name,
                        'pricing_model' => $rule->model->value,
                        'rate_cents' => $rule->rateCents,
                        'pricing_rule_version' => $rule->version,
                    ]],
                );

                return $rule;
            },
        );

        return new CreatePricingRuleOutput($stored);
    }
}
