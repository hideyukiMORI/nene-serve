<?php

declare(strict_types=1);

namespace NeneServe\Retention\LegalHolds;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Retention\LegalHold;
use NeneServe\Retention\PdoLegalHoldRepository;
use NeneServe\Retention\UseCase\LegalHoldException;
use NeneServe\Tenant\AuthContext;

/**
 * Places / releases legal holds. While any hold is active, retention purges are
 * blocked (billing §7, ADR 0022 §7). Holds are tombstoned (released_at), never
 * deleted; each change is audited atomically (NENE2 transaction pattern).
 */
final readonly class LegalHoldUseCase implements LegalHoldUseCaseInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private DatabaseTransactionManagerInterface $transactions,
    ) {
    }

    public function place(AuthContext $actor, string $reason): LegalHold
    {
        if (trim($reason) === '') {
            throw new LegalHoldException('reason is required.');
        }

        $hold = new LegalHold(
            'lh-' . bin2hex(random_bytes(8)),
            $actor->organizationId,
            trim($reason),
            gmdate('c'),
        );

        return $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($hold, $actor): LegalHold {
                (new PdoLegalHoldRepository($tx))->save($hold);
                (new PdoAuditLog($tx))->record(
                    $actor->organizationId,
                    $actor->userId,
                    'legal_hold.placed',
                    'legal_hold',
                    $hold->id,
                    ['reason' => $hold->reason],
                );

                return $hold;
            },
        );
    }

    public function release(AuthContext $actor, string $holdId): LegalHold
    {
        $hold = (new PdoLegalHoldRepository($this->query))->findByIdInOrganization($holdId, $actor->organizationId);

        if ($hold === null) {
            throw new LegalHoldException('Unknown legal hold.');
        }

        if (!$hold->isActive()) {
            throw new LegalHoldException('Legal hold already released.');
        }

        $released = $hold->release(gmdate('c'));

        return $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($released, $actor): LegalHold {
                (new PdoLegalHoldRepository($tx))->save($released);
                (new PdoAuditLog($tx))->record(
                    $actor->organizationId,
                    $actor->userId,
                    'legal_hold.released',
                    'legal_hold',
                    $released->id,
                    [],
                );

                return $released;
            },
        );
    }
}
