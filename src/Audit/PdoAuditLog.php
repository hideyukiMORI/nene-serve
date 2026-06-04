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
        $occurredAt = gmdate('Y-m-d H:i:s');
        $previousHash = $this->headHash($organizationId);
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

        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_events
                (id, organization_id, actor_user_id, action, subject_type, subject_id, metadata, occurred_at, previous_hash, hash)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        );
        $stmt->execute([
            bin2hex(random_bytes(8)),
            $organizationId,
            $actorUserId,
            $action,
            $subjectType,
            $subjectId,
            (string) json_encode($metadata),
            $occurredAt,
            $previousHash,
            $hash,
        ]);
    }

    public function forSubject(string $organizationId, string $subjectType, string $subjectId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::COLUMNS . '
             FROM audit_events
             WHERE organization_id = ? AND subject_type = ? AND subject_id = ?
             ORDER BY seq DESC',
        );
        $stmt->execute([$organizationId, $subjectType, $subjectId]);

        return $this->hydrateAll($stmt->fetchAll());
    }

    public function allForOrganization(string $organizationId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::COLUMNS . ' FROM audit_events WHERE organization_id = ? ORDER BY seq ASC',
        );
        $stmt->execute([$organizationId]);

        return $this->hydrateAll($stmt->fetchAll());
    }

    private const COLUMNS = 'id, organization_id, actor_user_id, action, subject_type, subject_id, metadata, occurred_at, previous_hash, hash';

    private function headHash(string $organizationId): string
    {
        $stmt = $this->pdo->prepare('SELECT hash FROM audit_events WHERE organization_id = ? ORDER BY seq DESC LIMIT 1');
        $stmt->execute([$organizationId]);
        $hash = $stmt->fetchColumn();

        return is_string($hash) ? $hash : '';
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return list<AuditEvent>
     */
    private function hydrateAll(array $rows): array
    {
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
                    (string) $row['previous_hash'],
                    (string) $row['hash'],
                );
            },
            array_values($rows),
        );
    }
}
