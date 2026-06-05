<?php

declare(strict_types=1);

namespace NeneServe\Measurement\Dsr;

use Nene2\Http\RequestScopedHolder;
use NeneServe\Audit\AuditLogInterface;
use NeneServe\Measurement\EventStoreInterface;

/**
 * Data-subject access/erasure on measurement data (privacy §5), keyed by the
 * hashed `visitor_bucket`. Tenant-scoped; both kinds are audited. Erasure is an
 * **additive tombstone** — counts are never edited (billing substantiation
 * retained under the billing carve-out).
 */
final readonly class DataSubjectRequestUseCase implements DataSubjectRequestUseCaseInterface
{
    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private EventStoreInterface $events,
        private AuditLogInterface $audit,
        private RequestScopedHolder $organizationId,
    ) {
    }

    public function export(ExportVisitorDataInput $input): ExportVisitorDataOutput
    {
        $organizationId = $this->organizationId->get();
        $data = $this->events->exportVisitorData($organizationId, $input->visitorBucket);

        $this->audit->record(
            $organizationId,
            $input->actorUserId,
            'dsr.export',
            'visitor_bucket',
            $input->visitorBucket,
            ['record_count' => count($data)],
        );

        return new ExportVisitorDataOutput($data);
    }

    public function erase(EraseVisitorDataInput $input): EraseVisitorDataOutput
    {
        $organizationId = $this->organizationId->get();
        $count = $this->events->eraseVisitor($organizationId, $input->visitorBucket);

        $this->audit->record(
            $organizationId,
            $input->actorUserId,
            'dsr.erasure',
            'visitor_bucket',
            $input->visitorBucket,
            ['tombstoned_count' => $count],
        );

        return new EraseVisitorDataOutput($count);
    }
}
