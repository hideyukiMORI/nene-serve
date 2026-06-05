<?php

declare(strict_types=1);

namespace NeneServe\Retention\LegalHolds;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Retention\LegalHold;
use NeneServe\Retention\LegalHoldRepositoryInterface;
use NeneServe\Retention\PdoLegalHoldRepository;
use NeneServe\Retention\UseCase\LegalHoldException;

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
    ) {
    }

    public function place(PlaceLegalHoldInput $input): PlaceLegalHoldOutput
    {
        if (trim($input->reason) === '') {
            throw new LegalHoldException('reason is required.');
        }

        $hold = new LegalHold(
            'lh-' . bin2hex(random_bytes(8)),
            $this->organizationId->get(),
            trim($input->reason),
            gmdate('c'),
        );

        $stored = $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($hold, $input): LegalHold {
                (new PdoLegalHoldRepository($tx))->save($hold);
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

        $released = $hold->release(gmdate('c'));

        $stored = $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($released, $input): LegalHold {
                (new PdoLegalHoldRepository($tx))->save($released);
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
