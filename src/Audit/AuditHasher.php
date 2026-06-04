<?php

declare(strict_types=1);

namespace NeneServe\Audit;

/**
 * Computes the tamper-evident hash for an audit record (ADR 0022 §5): SHA-256
 * over the record's fields plus the previous row's hash, forming a per-tenant
 * chain. The first record in a tenant chains from the empty genesis hash.
 */
final class AuditHasher
{
    /**
     * @param array<string, mixed> $metadata
     */
    public static function compute(
        string $organizationId,
        string $actorUserId,
        string $action,
        string $subjectType,
        string $subjectId,
        array $metadata,
        string $occurredAt,
        string $previousHash,
    ): string {
        $payload = implode("\x1f", [
            $organizationId,
            $actorUserId,
            $action,
            $subjectType,
            $subjectId,
            (string) json_encode($metadata),
            $occurredAt,
            $previousHash,
        ]);

        return hash('sha256', $payload);
    }

    public static function of(AuditEvent $event): string
    {
        return self::compute(
            $event->organizationId,
            $event->actorUserId,
            $event->action,
            $event->subjectType,
            $event->subjectId,
            $event->metadata,
            $event->occurredAt,
            $event->previousHash,
        );
    }
}
