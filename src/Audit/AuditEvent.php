<?php

declare(strict_types=1);

namespace NeneServe\Audit;

/**
 * Append-only audit record (ADR 0006/0022, creative-review §6). Captures
 * who/when/what; `hash`/`previousHash` form a per-tenant tamper-evident chain
 * (ADR 0022 §5) so any edit or gap in the trail is detectable.
 */
final class AuditEvent
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public readonly string $id,
        public readonly string $organizationId,
        public readonly string $actorUserId,
        public readonly string $action,
        public readonly string $subjectType,
        public readonly string $subjectId,
        public readonly array $metadata,
        public readonly string $occurredAt,
        public readonly string $previousHash = '',
        public readonly string $hash = '',
    ) {
    }
}
