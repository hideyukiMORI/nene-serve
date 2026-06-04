<?php

declare(strict_types=1);

namespace NeneServe\Serving\UseCase;

use NeneServe\Audit\AuditLogInterface;
use NeneServe\Serving\Placement;
use NeneServe\Serving\PlacementRepositoryInterface;
use NeneServe\Support\TransactionManagerInterface;
use NeneServe\Tenant\AuthContext;

final class CreatePlacementUseCase
{
    public function __construct(
        private readonly PlacementRepositoryInterface $placements,
        private readonly AuditLogInterface $audit,
        private readonly TransactionManagerInterface $tx,
    ) {
    }

    /**
     * @param list<string> $allowedOrigins
     */
    public function execute(
        AuthContext $actor,
        string $publicPlacementKey,
        array $allowedOrigins,
        ?string $defaultCreativeId = null,
        string $status = 'draft',
    ): Placement {
        if ($publicPlacementKey === '') {
            throw new CreativeValidationException('public_placement_key is required.');
        }

        $placement = new Placement(
            'plc-' . bin2hex(random_bytes(8)),
            $actor->organizationId,
            $publicPlacementKey,
            $allowedOrigins,
            $status,
            $defaultCreativeId,
        );

        // Mutation + audit commit together (audit-and-data-integrity §2).
        return $this->tx->transactional(function () use ($placement, $actor, $publicPlacementKey, $status): Placement {
            $this->placements->save($placement);
            $this->audit->record(
                $actor->organizationId,
                $actor->userId,
                'placement.created',
                'placement',
                $placement->id,
                ['after' => ['public_placement_key' => $publicPlacementKey, 'status' => $status]],
            );

            return $placement;
        });
    }
}
