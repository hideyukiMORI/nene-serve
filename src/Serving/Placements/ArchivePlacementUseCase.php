<?php

declare(strict_types=1);

namespace NeneServe\Serving\Placements;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Serving\PdoPlacementRepository;
use NeneServe\Serving\Placement;
use NeneServe\Serving\UseCase\PlacementNotFoundException;
use NeneServe\Tenant\AuthContext;

/**
 * Lifecycle "delete" for a placement = archive tombstone (ADR 0022 §3): the row
 * is retained, the placement stops serving, and the change is audited atomically
 * with the mutation (NENE2 transaction pattern).
 */
final readonly class ArchivePlacementUseCase implements ArchivePlacementUseCaseInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private DatabaseTransactionManagerInterface $transactions,
    ) {
    }

    public function execute(AuthContext $actor, string $placementId): Placement
    {
        $placement = (new PdoPlacementRepository($this->query))->findByIdInOrganization($placementId, $actor->organizationId);

        if ($placement === null) {
            throw new PlacementNotFoundException();
        }

        $at = gmdate('c');

        return $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($placement, $actor, $placementId, $at): Placement {
                (new PdoPlacementRepository($tx))->archive($placementId, $actor->organizationId, $at);
                (new PdoAuditLog($tx))->record(
                    $actor->organizationId,
                    $actor->userId,
                    'placement.archived',
                    'placement',
                    $placementId,
                    ['before' => ['status' => $placement->status], 'after' => ['status' => 'archived', 'archived_at' => $at]],
                );

                return $placement->archive($at);
            },
        );
    }
}
