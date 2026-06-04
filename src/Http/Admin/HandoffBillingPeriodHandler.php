<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Marketplace\UseCase\BillingPeriodNotFoundException;
use NeneServe\Marketplace\UseCase\HandoffBillingPeriodUseCase;
use NeneServe\Marketplace\UseCase\HandoffFailedException;
use NeneServe\Marketplace\UseCase\InvalidPeriodTransitionException;
use NeneServe\Marketplace\UseCase\ReconciliationFailedException;
use NeneServe\Tenant\AuthContext;

/**
 * POST /admin/billing-periods/{id}/handoff (operationId `handoffBillingPeriod`).
 * Requires `manage_marketplace`. Reconciles the closed period and hands the net
 * amount to Invoice; idempotent on external_reference. A transport failure (502)
 * does not pause serving and is safe to retry.
 */
final class HandoffBillingPeriodHandler
{
    public function __construct(
        private readonly HandoffBillingPeriodUseCase $handoff,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        try {
            $record = $this->handoff->execute($context, (string) $request->param('id'));
        } catch (BillingPeriodNotFoundException) {
            return $this->json->problem(404, 'billing-period-not-found', 'Billing period not found');
        } catch (InvalidPeriodTransitionException $e) {
            return $this->json->problem(409, 'invalid-period-transition', 'Cannot hand off', $e->getMessage());
        } catch (ReconciliationFailedException $e) {
            return $this->json->problem(409, 'reconciliation-failed', 'Reconciliation discrepancy', $e->getMessage());
        } catch (HandoffFailedException $e) {
            return $this->json->problem(502, 'invoice-handoff-failed', 'Invoice handoff failed', $e->getMessage());
        }

        return $this->json->ok($record->toArray());
    }
}
