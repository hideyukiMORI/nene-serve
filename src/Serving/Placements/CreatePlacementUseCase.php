<?php

declare(strict_types=1);

namespace NeneServe\Serving\Placements;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Serving\PdoPlacementRepository;
use NeneServe\Serving\Placement;
use NeneServe\Serving\UseCase\CreativeValidationException;
use NeneServe\Tenant\AuthContext;

/**
 * Creates a placement; the mutation and its audit entry commit together
 * (audit-and-data-integrity §2), using the NENE2 transaction pattern.
 */
final readonly class CreatePlacementUseCase implements CreatePlacementUseCaseInterface
{
    public function __construct(
        private DatabaseTransactionManagerInterface $transactions,
    ) {
    }

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

        return $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($placement, $actor, $publicPlacementKey, $status): Placement {
                (new PdoPlacementRepository($tx))->save($placement);
                (new PdoAuditLog($tx))->record(
                    $actor->organizationId,
                    $actor->userId,
                    'placement.created',
                    'placement',
                    $placement->id,
                    ['after' => ['public_placement_key' => $publicPlacementKey, 'status' => $status]],
                );

                return $placement;
            },
        );
    }
}
