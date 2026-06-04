<?php

declare(strict_types=1);

namespace NeneServe\Mcp\UseCase;

use NeneServe\Audit\AuditLogInterface;
use NeneServe\Mcp\ChangePlan;
use NeneServe\Mcp\ChangePlanRepositoryInterface;
use NeneServe\Service\ServiceContext;
use NeneServe\Serving\CreativeRepositoryInterface;
use NeneServe\Serving\PlacementRepositoryInterface;
use NeneServe\Support\TransactionManagerInterface;

/**
 * Applies a previously proposed change after explicit confirmation (the plan id /
 * confirmation token). Re-validates that the creative is still approved, applies
 * the change atomically, marks the plan applied, and audits it (api-security §5).
 * A non-proposed plan cannot be re-applied (idempotency).
 */
final class ApplyChangePlanUseCase
{
    public function __construct(
        private readonly PlacementRepositoryInterface $placements,
        private readonly CreativeRepositoryInterface $creatives,
        private readonly ChangePlanRepositoryInterface $plans,
        private readonly AuditLogInterface $audit,
        private readonly TransactionManagerInterface $tx,
    ) {
    }

    public function execute(ServiceContext $context, string $planId): ChangePlan
    {
        $plan = $this->plans->findByIdInOrganization($planId, $context->organizationId);
        if ($plan === null) {
            throw new ChangePlanNotFoundException();
        }
        if (!$plan->isProposed()) {
            throw new InvalidPlanStateException('Plan already applied.');
        }

        $placement = $this->placements->findByIdInOrganization($plan->placementId, $context->organizationId);
        if ($placement === null) {
            throw new McpValidationException('Placement no longer exists.');
        }
        $creative = $this->creatives->findByIdInOrganization($plan->newCreativeId, $context->organizationId);
        if ($creative === null || !$creative->isServable()) {
            throw new McpValidationException('Creative is no longer approved; change refused.');
        }

        $before = $placement->defaultCreativeId;
        $updated = $placement->withDefaultCreative($plan->newCreativeId);
        $applied = $plan->withStatus('applied');

        return $this->tx->transactional(function () use ($updated, $applied, $context, $before): ChangePlan {
            $this->placements->save($updated);
            $this->plans->save($applied);
            $this->audit->record(
                $context->organizationId,
                'service-token',
                'mcp.change_applied',
                'placement',
                $updated->id,
                [
                    'plan_id' => $applied->id,
                    'before' => ['default_creative_id' => $before],
                    'after' => ['default_creative_id' => $updated->defaultCreativeId],
                ],
            );

            return $applied;
        });
    }
}
