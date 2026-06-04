<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Marketplace\BillingPeriodRepositoryInterface;
use NeneServe\Marketplace\SpendSnapshotRepositoryInterface;
use NeneServe\Tenant\AuthContext;

/**
 * GET /admin/billing-periods/{id} (operationId `getBillingPeriod`). Requires
 * `manage_marketplace`. Returns the period and its latest immutable spend snapshot.
 */
final class GetBillingPeriodHandler
{
    public function __construct(
        private readonly BillingPeriodRepositoryInterface $periods,
        private readonly SpendSnapshotRepositoryInterface $snapshots,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        $period = $this->periods->findByIdInOrganization((string) $request->param('id'), $context->organizationId);
        if ($period === null) {
            return $this->json->problem(404, 'billing-period-not-found', 'Billing period not found');
        }

        $latest = $this->snapshots->latestForPeriod($context->organizationId, $period->id);

        return $this->json->ok($period->toArray() + ['latest_snapshot' => $latest?->toArray()]);
    }
}
