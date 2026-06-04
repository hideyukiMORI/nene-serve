<?php

declare(strict_types=1);

namespace NeneServe\Http\Service;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Mcp\UseCase\McpValidationException;
use NeneServe\Mcp\UseCase\ProposePlacementChangeUseCase;
use NeneServe\Service\ServiceContext;

/**
 * POST /api/delivery-plan-changes (operationId `proposeDeliveryPlanChange`,
 * service surface). Requires the `write:delivery_plan` scope. Returns a plan +
 * confirmation token; nothing changes until it is applied (api-security §5).
 */
final class ProposeChangeHandler
{
    public function __construct(
        private readonly ProposePlacementChangeUseCase $propose,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, ServiceContext $context): Response
    {
        $body = $request->json();
        $placementId = $body['placement_id'] ?? null;
        $newCreativeId = $body['new_creative_id'] ?? null;
        if (!is_string($placementId) || !is_string($newCreativeId)) {
            return $this->json->problem(422, 'validation-failed', 'placement_id and new_creative_id are required');
        }

        try {
            $plan = $this->propose->execute($context, $placementId, $newCreativeId);
        } catch (McpValidationException $e) {
            return $this->json->problem(422, 'validation-failed', 'Invalid change plan', $e->getMessage());
        }

        return $this->json->ok($plan->toArray(), 201);
    }
}
