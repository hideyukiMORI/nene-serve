<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Marketplace\UseCase\MarketplaceValidationException;
use NeneServe\Marketplace\UseCase\OpenBillingPeriodUseCase;
use NeneServe\Tenant\AuthContext;

/** POST /admin/campaigns/{id}/billing-periods (operationId `openBillingPeriod`). Requires `manage_marketplace`. */
final class OpenBillingPeriodHandler
{
    public function __construct(
        private readonly OpenBillingPeriodUseCase $open,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        $body = $request->json();
        $start = $body['period_start'] ?? null;
        $end = $body['period_end'] ?? null;
        if (!is_string($start) || !is_string($end)) {
            return $this->json->problem(422, 'validation-failed', 'period_start and period_end (YYYY-MM-DD) are required');
        }

        try {
            $period = $this->open->execute($context, (string) $request->param('id'), $start, $end);
        } catch (MarketplaceValidationException $e) {
            return $this->json->problem(422, 'validation-failed', 'Invalid billing period', $e->getMessage());
        }

        return $this->json->ok($period->toArray(), 201);
    }
}
