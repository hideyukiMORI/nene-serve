<?php

declare(strict_types=1);

namespace NeneServe\Retention\UseCase;

use NeneServe\Audit\AuditLogInterface;
use NeneServe\Retention\LegalHold;
use NeneServe\Retention\LegalHoldRepositoryInterface;
use NeneServe\Support\TransactionManagerInterface;
use NeneServe\Tenant\AuthContext;

final class PlaceLegalHoldUseCase
{
    public function __construct(
        private readonly LegalHoldRepositoryInterface $legalHolds,
        private readonly AuditLogInterface $audit,
        private readonly TransactionManagerInterface $tx,
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

        return $this->tx->transactional(function () use ($hold, $actor): LegalHold {
            $this->legalHolds->save($hold);
            $this->audit->record(
                $actor->organizationId,
                $actor->userId,
                'legal_hold.placed',
                'legal_hold',
                $hold->id,
                ['reason' => $hold->reason],
            );

            return $hold;
        });
    }

    public function release(AuthContext $actor, string $holdId): LegalHold
    {
        $hold = $this->legalHolds->findByIdInOrganization($holdId, $actor->organizationId);
        if ($hold === null) {
            throw new LegalHoldException('Unknown legal hold.');
        }
        if (!$hold->isActive()) {
            throw new LegalHoldException('Legal hold already released.');
        }

        $released = $hold->release(gmdate('c'));

        return $this->tx->transactional(function () use ($released, $actor): LegalHold {
            $this->legalHolds->save($released);
            $this->audit->record(
                $actor->organizationId,
                $actor->userId,
                'legal_hold.released',
                'legal_hold',
                $released->id,
                [],
            );

            return $released;
        });
    }
}
