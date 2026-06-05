<?php

declare(strict_types=1);

namespace NeneServe\Mcp\Api;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Mcp\ChangePlan;
use NeneServe\Mcp\PdoChangePlanRepository;
use NeneServe\Mcp\UseCase\ChangePlanNotFoundException;
use NeneServe\Mcp\UseCase\InvalidPlanStateException;
use NeneServe\Mcp\UseCase\McpValidationException;
use NeneServe\Service\ServiceContext;
use NeneServe\Serving\PdoCreativeRepository;
use NeneServe\Serving\PdoPlacementRepository;

/**
 * Applies a previously proposed change after explicit confirmation (the plan id /
 * confirmation token). Re-validates that the creative is still approved, applies
 * the change atomically, marks the plan applied, and audits it (api-security §5).
 * A non-proposed plan cannot be re-applied (idempotency).
 */
final readonly class ApplyChangePlanUseCase implements ApplyChangePlanUseCaseInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private DatabaseTransactionManagerInterface $transactions,
    ) {
    }

    public function execute(ServiceContext $context, string $planId): ChangePlan
    {
        $plan = (new PdoChangePlanRepository($this->query))->findByIdInOrganization($planId, $context->organizationId);

        if ($plan === null) {
            throw new ChangePlanNotFoundException();
        }

        if (!$plan->isProposed()) {
            throw new InvalidPlanStateException('Plan already applied.');
        }

        $placement = (new PdoPlacementRepository($this->query))->findByIdInOrganization($plan->placementId, $context->organizationId);

        if ($placement === null) {
            throw new McpValidationException('Placement no longer exists.');
        }

        $creative = (new PdoCreativeRepository($this->query))->findByIdInOrganization($plan->newCreativeId, $context->organizationId);

        if ($creative === null || !$creative->isServable()) {
            throw new McpValidationException('Creative is no longer approved; change refused.');
        }

        $before = $placement->defaultCreativeId;
        $updated = $placement->withDefaultCreative($plan->newCreativeId);
        $applied = $plan->withStatus('applied');

        return $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($updated, $applied, $context, $before): ChangePlan {
                (new PdoPlacementRepository($tx))->save($updated);
                (new PdoChangePlanRepository($tx))->save($applied);
                (new PdoAuditLog($tx))->record(
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
            },
        );
    }
}
