<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\UseCase;

use NeneServe\Audit\AuditLogInterface;
use NeneServe\Marketplace\PricingModel;
use NeneServe\Marketplace\PricingRule;
use NeneServe\Marketplace\PricingRuleRepositoryInterface;
use NeneServe\Money\Money;
use NeneServe\Support\TransactionManagerInterface;
use NeneServe\Tenant\AuthContext;

/**
 * Creates a new **immutable** pricing-rule version (billing §3.3). Reusing a
 * `name` bumps the version; the prior version is retained so historical figures
 * stay reproducible. `rate_cents` is validated as net money (integer, JPY,
 * non-negative, no tax) via {@see Money}.
 */
final class CreatePricingRuleUseCase
{
    public function __construct(
        private readonly PricingRuleRepositoryInterface $rules,
        private readonly AuditLogInterface $audit,
        private readonly TransactionManagerInterface $tx,
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

        $version = $this->rules->currentVersion($actor->organizationId, trim($name)) + 1;
        $rule = new PricingRule(
            'pr-' . bin2hex(random_bytes(8)),
            $actor->organizationId,
            trim($name),
            $pricingModel,
            $rate->cents,
            $version,
            gmdate('c'),
        );

        return $this->tx->transactional(function () use ($rule, $actor): PricingRule {
            $this->rules->save($rule);
            $this->audit->record(
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
        });
    }
}
