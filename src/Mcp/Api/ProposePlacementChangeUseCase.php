<?php

declare(strict_types=1);

namespace NeneServe\Mcp\Api;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\ClockInterface;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Mcp\ChangePlan;
use NeneServe\Mcp\PdoChangePlanRepository;
use NeneServe\Mcp\UseCase\McpValidationException;
use NeneServe\Service\ServiceContext;
use NeneServe\Serving\PdoCreativeRepository;
use NeneServe\Serving\PdoPlacementRepository;
use NeneServe\Support\Id;
use NeneServe\Support\SqlDialect;

/**
 * Proposes a delivery-plan change (place a different default creative) without
 * applying it (api-security §5). Validates the target placement and that the new
 * creative is **approved** (only approved serves); records the plan + an audit
 * event. The returned plan id is the confirmation token required to apply it.
 */
final readonly class ProposePlacementChangeUseCase implements ProposePlacementChangeUseCaseInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private DatabaseTransactionManagerInterface $transactions,
        private ClockInterface $clock,
        private SqlDialect $dialect = SqlDialect::Mysql,
    ) {
    }

    public function execute(ServiceContext $context, string $placementId, string $newCreativeId): ChangePlan
    {
        if ((new PdoPlacementRepository($this->query))->findByIdInOrganization($placementId, $context->organizationId) === null) {
            throw new McpValidationException('Unknown placement.');
        }

        $creative = (new PdoCreativeRepository($this->query))->findByIdInOrganization($newCreativeId, $context->organizationId);

        if ($creative === null || !$creative->isServable()) {
            throw new McpValidationException('new_creative_id must be an approved creative.');
        }

        $plan = new ChangePlan(
            Id::generate('plan', 12),
            $context->organizationId,
            $placementId,
            $newCreativeId,
            'proposed',
            $this->clock->now()->format('c'),
        );

        $dialect = $this->dialect;

        return $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($plan, $context, $dialect): ChangePlan {
                (new PdoChangePlanRepository($tx, $dialect))->save($plan);
                (new PdoAuditLog($tx))->record(
                    $context->organizationId,
                    'service-token',
                    'mcp.change_proposed',
                    'change_plan',
                    $plan->id,
                    ['placement_id' => $plan->placementId, 'new_creative_id' => $plan->newCreativeId],
                );

                return $plan;
            },
        );
    }
}
