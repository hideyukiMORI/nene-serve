<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Marketplace\UseCase\CreateCampaignUseCase;
use NeneServe\Marketplace\UseCase\MarketplaceValidationException;
use NeneServe\Tenant\AuthContext;

/**
 * POST /admin/campaigns (operationId `createCampaign`). Requires
 * `manage_marketplace`. `budget_cents` is net integer money (no tax).
 */
final class CreateCampaignHandler
{
    public function __construct(
        private readonly CreateCampaignUseCase $createCampaign,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        $body = $request->json();
        $advertiserId = $body['advertiser_id'] ?? null;
        $name = $body['name'] ?? null;
        $pricingRuleId = $body['pricing_rule_id'] ?? null;
        $budgetCents = $body['budget_cents'] ?? null;
        if (!is_string($advertiserId) || !is_string($name) || !is_string($pricingRuleId) || !is_int($budgetCents)) {
            return $this->json->problem(
                422,
                'validation-failed',
                'advertiser_id, name, pricing_rule_id (string) and budget_cents (integer net cents) are required',
            );
        }

        $pause = ($body['pause_on_budget_exhausted'] ?? true) !== false;
        $status = is_string($body['status'] ?? null) ? $body['status'] : 'draft';
        $fundingStatus = is_string($body['funding_status'] ?? null) ? $body['funding_status'] : 'unfunded';

        try {
            $campaign = $this->createCampaign->execute($context, $advertiserId, $name, $pricingRuleId, $budgetCents, $pause, $status, $fundingStatus);
        } catch (MarketplaceValidationException $e) {
            return $this->json->problem(422, 'validation-failed', 'Invalid campaign', $e->getMessage());
        }

        return $this->json->ok($campaign->toArray(), 201);
    }
}
