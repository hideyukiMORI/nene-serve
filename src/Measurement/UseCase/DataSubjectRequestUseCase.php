<?php

declare(strict_types=1);

namespace NeneServe\Measurement\UseCase;

use NeneServe\Audit\AuditLogInterface;
use NeneServe\Measurement\EventStoreInterface;
use NeneServe\Tenant\AuthContext;

/**
 * Data-subject access/erasure on measurement data (privacy §5), keyed by the
 * hashed `visitor_bucket`. Tenant-scoped; both kinds are audited. Erasure is an
 * **additive tombstone** — counts are never edited (billing substantiation
 * retained under the billing carve-out).
 */
final class DataSubjectRequestUseCase
{
    public function __construct(
        private readonly EventStoreInterface $events,
        private readonly AuditLogInterface $audit,
    ) {
    }

    /**
     * @return list<array{type: string, date: string, placement_id: string, creative_id: string}>
     */
    public function export(AuthContext $actor, string $visitorBucket): array
    {
        $data = $this->events->exportVisitorData($actor->organizationId, $visitorBucket);

        $this->audit->record(
            $actor->organizationId,
            $actor->userId,
            'dsr.export',
            'visitor_bucket',
            $visitorBucket,
            ['record_count' => count($data)],
        );

        return $data;
    }

    public function erase(AuthContext $actor, string $visitorBucket): int
    {
        $count = $this->events->eraseVisitor($actor->organizationId, $visitorBucket);

        $this->audit->record(
            $actor->organizationId,
            $actor->userId,
            'dsr.erasure',
            'visitor_bucket',
            $visitorBucket,
            ['tombstoned_count' => $count],
        );

        return $count;
    }
}
