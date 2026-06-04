<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Marketplace\UseCase\CreatePricingRuleUseCase;
use NeneServe\Marketplace\UseCase\MarketplaceValidationException;
use NeneServe\Tenant\AuthContext;

/**
 * POST /admin/pricing-rules (operationId `createPricingRule`). Requires
 * `manage_marketplace`. `rate_cents` is net integer money (no tax); reusing a
 * `name` creates a new version.
 */
final class CreatePricingRuleHandler
{
    public function __construct(
        private readonly CreatePricingRuleUseCase $createPricingRule,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        $body = $request->json();
        $name = $body['name'] ?? null;
        $model = $body['pricing_model'] ?? null;
        $rateCents = $body['rate_cents'] ?? null;
        if (!is_string($name) || !is_string($model) || !is_int($rateCents)) {
            return $this->json->problem(
                422,
                'validation-failed',
                'name, pricing_model (string) and rate_cents (integer net cents) are required',
            );
        }

        try {
            $rule = $this->createPricingRule->execute($context, $name, $model, $rateCents);
        } catch (MarketplaceValidationException $e) {
            return $this->json->problem(422, 'validation-failed', 'Invalid pricing rule', $e->getMessage());
        }

        return $this->json->ok($rule->toArray(), 201);
    }
}
