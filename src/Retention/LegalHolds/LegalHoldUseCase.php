<?php

declare(strict_types=1);

namespace NeneServe\Retention\LegalHolds;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\ClockInterface;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Retention\LegalHold;
use NeneServe\Retention\LegalHoldRepositoryInterface;
use NeneServe\Retention\PdoLegalHoldRepository;
use NeneServe\Retention\UseCase\LegalHoldException;
use NeneServe\Support\Id;
use NeneServe\Support\SqlDialect;

/**
 * Places / releases legal holds. While any hold is active, retention purges are
 * blocked (billing §7, ADR 0022 §7). Holds are tombstoned (released_at), never
 * deleted; each change is audited atomically (NENE2 transaction pattern).
 */
final readonly class LegalHoldUseCase implements LegalHoldUseCaseInterface
{
    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private LegalHoldRepositoryInterface $holds,
        private DatabaseTransactionManagerInterface $transactions,
        private RequestScopedHolder $organizationId,
        private ClockInterface $clock,
        private SqlDialect $dialect = SqlDialect::Mysql,
    ) {
    }

    public function place(PlaceLegalHoldInput $input): PlaceLegalHoldOutput
    {
        if (trim($input->reason) === '') {
            throw new LegalHoldException('reason is required.');
        }

        $hold = new LegalHold(
            Id::generate('lh'),
            $this->organizationId->get(),
            trim($input->reason),
            $this->clock->now()->format('c'),
        );

        $dialect = $this->dialect;
        $stored = $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($hold, $input, $dialect): LegalHold {
                (new PdoLegalHoldRepository($tx, $dialect))->save($hold);
                (new PdoAuditLog($tx))->record(
                    $hold->organizationId,
                    $input->actorUserId,
                    'legal_hold.placed',
                    'legal_hold',
                    $hold->id,
                    ['reason' => $hold->reason],
                );

                return $hold;
            },
        );

        return new PlaceLegalHoldOutput($stored);
    }

    public function release(ReleaseLegalHoldInput $input): ReleaseLegalHoldOutput
    {
        $hold = $this->holds->findByIdInOrganization($input->holdId, $this->organizationId->get());

        if ($hold === null) {
            throw new LegalHoldException('Unknown legal hold.');
        }

        if (!$hold->isActive()) {
            throw new LegalHoldException('Legal hold already released.');
        }

        $released = $hold->release($this->clock->now()->format('c'));

        $dialect = $this->dialect;
        $stored = $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($released, $input, $dialect): LegalHold {
                (new PdoLegalHoldRepository($tx, $dialect))->save($released);
                (new PdoAuditLog($tx))->record(
                    $released->organizationId,
                    $input->actorUserId,
                    'legal_hold.released',
                    'legal_hold',
                    $released->id,
                    [],
                );

                return $released;
            },
        );

        return new ReleaseLegalHoldOutput($stored);
    }
}
