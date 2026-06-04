<?php

declare(strict_types=1);

namespace NeneServe\Audit;

final class InMemoryAuditLog implements AuditLogInterface
{
    /** @var list<AuditEvent> */
    private array $events = [];

    public function record(
        string $organizationId,
        string $actorUserId,
        string $action,
        string $subjectType,
        string $subjectId,
        array $metadata = [],
    ): void {
        $this->events[] = new AuditEvent(
            bin2hex(random_bytes(8)),
            $organizationId,
            $actorUserId,
            $action,
            $subjectType,
            $subjectId,
            $metadata,
            gmdate('c'),
        );
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
}
