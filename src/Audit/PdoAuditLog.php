<?php

declare(strict_types=1);

namespace NeneServe\Audit;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoAuditLog implements AuditLogInterface
{
    private const COLUMNS = 'id, organization_id, actor_user_id, action, subject_type, subject_id, metadata, occurred_at, previous_hash, hash';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
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

        $this->query->execute(
            'INSERT INTO audit_events
                (id, organization_id, actor_user_id, action, subject_type, subject_id, metadata, occurred_at, previous_hash, hash)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
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
            ],
        );
    }

    public function allForOrganization(string $organizationId): array
    {
        $rows = $this->query->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM audit_events WHERE organization_id = ? ORDER BY seq ASC',
            [$organizationId],
        );

        return $this->hydrateAll($rows);
    }

    private function headHash(string $organizationId): string
    {
        $row = $this->query->fetchOne(
            'SELECT hash FROM audit_events WHERE organization_id = ? ORDER BY seq DESC LIMIT 1',
            [$organizationId],
        );

        return is_string($row['hash'] ?? null) ? $row['hash'] : '';
    }

    /**
     * @param list<array<string, mixed>> $rows
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
            $rows,
        );
    }
}
