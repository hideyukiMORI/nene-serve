<?php

declare(strict_types=1);

namespace NeneServe\Http\Service;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Mcp\UseCase\ApplyChangePlanUseCase;
use NeneServe\Mcp\UseCase\ChangePlanNotFoundException;
use NeneServe\Mcp\UseCase\InvalidPlanStateException;
use NeneServe\Mcp\UseCase\McpValidationException;
use NeneServe\Service\ServiceContext;

/**
 * POST /api/delivery-plan-changes/{token}/apply (operationId
 * `applyDeliveryPlanChange`, service surface). Requires `write:delivery_plan`.
 * Applies the confirmed plan; audited; a non-proposed plan → 409.
 */
final class ApplyChangeHandler
{
    public function __construct(
        private readonly ApplyChangePlanUseCase $apply,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, ServiceContext $context): Response
    {
        try {
            $plan = $this->apply->execute($context, (string) $request->param('token'));
        } catch (ChangePlanNotFoundException) {
            return $this->json->problem(404, 'change-plan-not-found', 'Change plan not found');
        } catch (InvalidPlanStateException $e) {
            return $this->json->problem(409, 'invalid-plan-state', 'Plan cannot be applied', $e->getMessage());
        } catch (McpValidationException $e) {
            return $this->json->problem(422, 'validation-failed', 'Change refused', $e->getMessage());
        }

        return $this->json->ok($plan->toArray());
    }
}
