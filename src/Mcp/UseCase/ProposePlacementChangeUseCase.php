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
 * Proposes a delivery-plan change (place a different default creative) without
 * applying it (api-security §5). Validates the target placement and that the new
 * creative is **approved** (only approved serves); records the plan + an audit
 * event. The returned plan id is the confirmation token required to apply it.
 */
final class ProposePlacementChangeUseCase
{
    public function __construct(
        private readonly PlacementRepositoryInterface $placements,
        private readonly CreativeRepositoryInterface $creatives,
        private readonly ChangePlanRepositoryInterface $plans,
        private readonly AuditLogInterface $audit,
        private readonly TransactionManagerInterface $tx,
    ) {
    }

    public function execute(ServiceContext $context, string $placementId, string $newCreativeId): ChangePlan
    {
        if ($this->placements->findByIdInOrganization($placementId, $context->organizationId) === null) {
            throw new McpValidationException('Unknown placement.');
        }
        $creative = $this->creatives->findByIdInOrganization($newCreativeId, $context->organizationId);
        if ($creative === null || !$creative->isServable()) {
            throw new McpValidationException('new_creative_id must be an approved creative.');
        }

        $plan = new ChangePlan(
            'plan-' . bin2hex(random_bytes(12)),
            $context->organizationId,
            $placementId,
            $newCreativeId,
            'proposed',
            gmdate('c'),
        );

        return $this->tx->transactional(function () use ($plan, $context): ChangePlan {
            $this->plans->save($plan);
            $this->audit->record(
                $context->organizationId,
                'service-token',
                'mcp.change_proposed',
                'change_plan',
                $plan->id,
                ['placement_id' => $plan->placementId, 'new_creative_id' => $plan->newCreativeId],
            );

            return $plan;
        });
    }
}
