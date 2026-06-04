<?php

declare(strict_types=1);

namespace NeneServe\Serving\UseCase;

use NeneServe\Audit\AuditLogInterface;
use NeneServe\Serving\Placement;
use NeneServe\Serving\PlacementRepositoryInterface;
use NeneServe\Tenant\AuthContext;

final class CreatePlacementUseCase
{
    public function __construct(
        private readonly PlacementRepositoryInterface $placements,
        private readonly AuditLogInterface $audit,
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
        $this->placements->save($placement);

        $this->audit->record(
            $actor->organizationId,
            $actor->userId,
            'placement.created',
            'placement',
            $placement->id,
            ['public_placement_key' => $publicPlacementKey],
        );

        return $placement;
    }
}
