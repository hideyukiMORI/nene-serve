<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Marketplace\UseCase\BillingPeriodNotFoundException;
use NeneServe\Marketplace\UseCase\CloseBillingPeriodUseCase;
use NeneServe\Marketplace\UseCase\InvalidPeriodTransitionException;
use NeneServe\Marketplace\UseCase\MarketplaceValidationException;
use NeneServe\Tenant\AuthContext;

/**
 * POST /admin/billing-periods/{id}/close (operationId `closeBillingPeriod`).
 * Requires `manage_marketplace`. Freezes the period into an immutable, versioned
 * spend snapshot; re-closing is rejected (409).
 */
final class CloseBillingPeriodHandler
{
    public function __construct(
        private readonly CloseBillingPeriodUseCase $close,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        try {
            $result = $this->close->execute($context, (string) $request->param('id'));
        } catch (BillingPeriodNotFoundException) {
            return $this->json->problem(404, 'billing-period-not-found', 'Billing period not found');
        } catch (InvalidPeriodTransitionException $e) {
            return $this->json->problem(409, 'invalid-period-transition', 'Cannot close period', $e->getMessage());
        } catch (MarketplaceValidationException $e) {
            return $this->json->problem(422, 'validation-failed', 'Cannot close period', $e->getMessage());
        }

        return $this->json->ok([
            'period' => $result['period']->toArray(),
            'snapshot' => $result['snapshot']->toArray(),
        ]);
    }
}
