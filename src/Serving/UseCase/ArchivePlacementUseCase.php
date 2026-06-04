<?php

declare(strict_types=1);

namespace NeneServe\Serving\UseCase;

use NeneServe\Audit\AuditLogInterface;
use NeneServe\Serving\Placement;
use NeneServe\Serving\PlacementRepositoryInterface;
use NeneServe\Support\TransactionManagerInterface;
use NeneServe\Tenant\AuthContext;

/**
 * Lifecycle "delete" for a placement = archive tombstone (ADR 0022 §3): the row
 * is retained, the placement stops serving, and the change is audited atomically
 * with the mutation.
 */
final class ArchivePlacementUseCase
{
    public function __construct(
        private readonly PlacementRepositoryInterface $placements,
        private readonly AuditLogInterface $audit,
        private readonly TransactionManagerInterface $tx,
    ) {
    }

    public function execute(AuthContext $actor, string $placementId): Placement
    {
        $placement = $this->placements->findByIdInOrganization($placementId, $actor->organizationId);
        if ($placement === null) {
            throw new PlacementNotFoundException();
        }

        $at = gmdate('c');

        return $this->tx->transactional(function () use ($placement, $actor, $placementId, $at): Placement {
            $this->placements->archive($placementId, $actor->organizationId, $at);
            $this->audit->record(
                $actor->organizationId,
                $actor->userId,
                'placement.archived',
                'placement',
                $placementId,
                ['before' => ['status' => $placement->status], 'after' => ['status' => 'archived', 'archived_at' => $at]],
            );

            return $placement->archive($at);
        });
    }
}
