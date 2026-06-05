<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Marketplace\PdoPricingRuleRepository;
use NeneServe\Marketplace\PricingModel;
use NeneServe\Marketplace\PricingRule;
use NeneServe\Marketplace\UseCase\MarketplaceValidationException;
use NeneServe\Money\Money;
use NeneServe\Tenant\AuthContext;

/**
 * Pricing rules are immutable: a change is a new version row (billing-and-
 * accounting §3). Committed atomically via the NENE2 transaction pattern.
 */
final readonly class CreatePricingRuleUseCase implements CreatePricingRuleUseCaseInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private DatabaseTransactionManagerInterface $transactions,
    ) {
    }

    public function execute(AuthContext $actor, string $name, string $model, int $rateCents): PricingRule
    {
        if (trim($name) === '') {
            throw new MarketplaceValidationException('name is required.');
        }

        $pricingModel = PricingModel::tryFrom($model);

        if ($pricingModel === null) {
            throw new MarketplaceValidationException('pricing_model must be one of: cpm, cpc, flat.');
        }

        // Validates integer / JPY / non-negative / no-tax invariants.
        $rate = Money::fromCents($rateCents);

        $version = (new PdoPricingRuleRepository($this->query))->currentVersion($actor->organizationId, trim($name)) + 1;
        $rule = new PricingRule(
            'pr-' . bin2hex(random_bytes(8)),
            $actor->organizationId,
            trim($name),
            $pricingModel,
            $rate->cents,
            $version,
            gmdate('c'),
        );

        return $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($rule, $actor): PricingRule {
                (new PdoPricingRuleRepository($tx))->save($rule);
                (new PdoAuditLog($tx))->record(
                    $actor->organizationId,
                    $actor->userId,
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
    }
}
