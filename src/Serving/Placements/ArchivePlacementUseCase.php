<?php

declare(strict_types=1);

namespace NeneServe\Serving\Placements;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Serving\PdoPlacementRepository;
use NeneServe\Serving\Placement;
use NeneServe\Serving\PlacementRepositoryInterface;
use NeneServe\Serving\UseCase\PlacementNotFoundException;

/**
 * Lifecycle "delete" for a placement = archive tombstone (ADR 0022 §3): the row
 * is retained, the placement stops serving, and the change is audited atomically
 * with the mutation (NENE2 transaction pattern).
 */
final readonly class ArchivePlacementUseCase implements ArchivePlacementUseCaseInterface
{
    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private PlacementRepositoryInterface $placements,
        private DatabaseTransactionManagerInterface $transactions,
        private RequestScopedHolder $organizationId,
    ) {
    }

    public function execute(ArchivePlacementInput $input): ArchivePlacementOutput
    {
        $organizationId = $this->organizationId->get();

        $placement = $this->placements->findByIdInOrganization($input->placementId, $organizationId);

        if ($placement === null) {
            throw new PlacementNotFoundException();
        }

        $at = gmdate('c');

        $archived = $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($placement, $input, $organizationId, $at): Placement {
                (new PdoPlacementRepository($tx))->archive($input->placementId, $organizationId, $at);
                (new PdoAuditLog($tx))->record(
                    $organizationId,
                    $input->actorUserId,
                    'placement.archived',
                    'placement',
                    $input->placementId,
                    ['before' => ['status' => $placement->status], 'after' => ['status' => 'archived', 'archived_at' => $at]],
                );

                return $placement->archive($at);
            },
        );

        return new ArchivePlacementOutput($archived);
    }
}
