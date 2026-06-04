<?php

declare(strict_types=1);

namespace NeneServe\Audit;

final class InMemoryAuditLog implements AuditLogInterface
{
    /** @var list<AuditEvent> */
    private array $events = [];

    /** @var array<string, string> last hash per organization */
    private array $headByOrg = [];

    public function record(
        string $organizationId,
        string $actorUserId,
        string $action,
        string $subjectType,
        string $subjectId,
        array $metadata = [],
    ): void {
        $occurredAt = gmdate('c');
        $previousHash = $this->headByOrg[$organizationId] ?? '';
        $hash = AuditHasher::compute(
            $organizationId,
            $actorUserId,
            $action,
            $subjectType,
            $subjectId,
            $metadata,
            $occurredAt,
            $previousHash,
        );

        $this->events[] = new AuditEvent(
            bin2hex(random_bytes(8)),
            $organizationId,
            $actorUserId,
            $action,
            $subjectType,
            $subjectId,
            $metadata,
            $occurredAt,
            $previousHash,
            $hash,
        );
        $this->headByOrg[$organizationId] = $hash;
    }

    public function forSubject(string $organizationId, string $subjectType, string $subjectId): array
    {
        $matches = array_filter(
            $this->events,
            static fn (AuditEvent $e): bool => $e->organizationId === $organizationId
                && $e->subjectType === $subjectType
                && $e->subjectId === $subjectId,
        );

        return array_reverse(array_values($matches));
    }

    public function allForOrganization(string $organizationId): array
    {
        return array_values(array_filter(
            $this->events,
            static fn (AuditEvent $e): bool => $e->organizationId === $organizationId,
        ));
    }
}
