<?php

declare(strict_types=1);

namespace NeneServe\Serving\Placements;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Serving\PdoPlacementRepository;
use NeneServe\Serving\Placement;
use NeneServe\Serving\UseCase\CreativeValidationException;
use NeneServe\Support\Id;
use NeneServe\Support\SqlDialect;

/**
 * Creates a placement; the mutation and its audit entry commit together
 * (audit-and-data-integrity §2), using the NENE2 transaction pattern.
 */
final readonly class CreatePlacementUseCase implements CreatePlacementUseCaseInterface
{
    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private DatabaseTransactionManagerInterface $transactions,
        private RequestScopedHolder $organizationId,
        private SqlDialect $dialect = SqlDialect::Mysql,
    ) {
    }

    public function execute(CreatePlacementInput $input): CreatePlacementOutput
    {
        if ($input->publicPlacementKey === '') {
            throw new CreativeValidationException('public_placement_key is required.');
        }

        $organizationId = $this->organizationId->get();

        $placement = new Placement(
            Id::generate('plc'),
            $organizationId,
            $input->publicPlacementKey,
            $input->allowedOrigins,
            $input->status,
            $input->defaultCreativeId,
        );

        $dialect = $this->dialect;
        $stored = $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($placement, $input, $organizationId, $dialect): Placement {
                (new PdoPlacementRepository($tx, $dialect))->save($placement);
                (new PdoAuditLog($tx))->record(
                    $organizationId,
                    $input->actorUserId,
                    'placement.created',
                    'placement',
                    $placement->id,
                    ['after' => ['public_placement_key' => $input->publicPlacementKey, 'status' => $input->status]],
                );

                return $placement;
            },
        );

        return new CreatePlacementOutput($stored);
    }
}
