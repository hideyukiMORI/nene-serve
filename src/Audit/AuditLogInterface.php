<?php

declare(strict_types=1);

namespace NeneServe\Audit;

/**
 * Append-only audit sink. Records are never updated or deleted (creative-review
 * §6, ADR 0006).
 */
interface AuditLogInterface
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function record(
        string $organizationId,
        string $actorUserId,
        string $action,
        string $subjectType,
        string $subjectId,
        array $metadata = [],
    ): void;

    /**
     * Audit trail for one subject (newest first).
     *
     * @return list<AuditEvent>
     */
    public function forSubject(string $organizationId, string $subjectType, string $subjectId): array;

    /**
     * Full tenant chain, oldest → newest, for {@see AuditChainVerifier}.
     *
     * @return list<AuditEvent>
     */
    public function allForOrganization(string $organizationId): array;
}
