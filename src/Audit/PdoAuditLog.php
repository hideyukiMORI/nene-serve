<?php

declare(strict_types=1);

namespace NeneServe\Audit;

use PDO;

final class PdoAuditLog implements AuditLogInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function record(
        string $organizationId,
        string $actorUserId,
        string $action,
        string $subjectType,
        string $subjectId,
        array $metadata = [],
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_events
                (id, organization_id, actor_user_id, action, subject_type, subject_id, metadata, occurred_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        );
        $stmt->execute([
            bin2hex(random_bytes(8)),
            $organizationId,
            $actorUserId,
            $action,
            $subjectType,
            $subjectId,
            (string) json_encode($metadata),
            gmdate('Y-m-d H:i:s'),
        ]);
    }

    public function forSubject(string $organizationId, string $subjectType, string $subjectId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, organization_id, actor_user_id, action, subject_type, subject_id, metadata, occurred_at
             FROM audit_events
             WHERE organization_id = ? AND subject_type = ? AND subject_id = ?
             ORDER BY occurred_at DESC, id DESC',
        );
        $stmt->execute([$organizationId, $subjectType, $subjectId]);

        return array_map(
            static function (array $row): AuditEvent {
                /** @var array<string, mixed> $metadata */
                $metadata = json_decode((string) $row['metadata'], true) ?: [];

                return new AuditEvent(
                    (string) $row['id'],
                    (string) $row['organization_id'],
                    (string) $row['actor_user_id'],
                    (string) $row['action'],
                    (string) $row['subject_type'],
                    (string) $row['subject_id'],
                    $metadata,
                    (string) $row['occurred_at'],
                );
            },
            array_values($stmt->fetchAll()),
        );
    }
}
