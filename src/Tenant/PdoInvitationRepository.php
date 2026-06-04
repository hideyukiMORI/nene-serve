<?php

declare(strict_types=1);

namespace NeneServe\Tenant;

use PDO;

final class PdoInvitationRepository implements InvitationRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, user_id, token_hash, status, expires_at, accepted_at';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function findByTokenHash(string $tokenHash): ?Invitation
    {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::COLUMNS . ' FROM invitations WHERE token_hash = ? LIMIT 1',
        );
        $stmt->execute([$tokenHash]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    public function save(Invitation $invitation): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO invitations (id, organization_id, user_id, token_hash, status, expires_at, accepted_at)
             VALUES (:id, :org, :user, :hash, :status, :expires_at, :accepted_at) AS new
             ON DUPLICATE KEY UPDATE status = new.status, accepted_at = new.accepted_at',
        );
        $stmt->execute([
            ':id' => $invitation->id,
            ':org' => $invitation->organizationId,
            ':user' => $invitation->userId,
            ':hash' => $invitation->tokenHash,
            ':status' => $invitation->status,
            ':expires_at' => $invitation->expiresAt,
            ':accepted_at' => $invitation->acceptedAt,
        ]);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): Invitation
    {
        return new Invitation(
            (string) $row['id'],
            (string) $row['organization_id'],
            (string) $row['user_id'],
            (string) $row['token_hash'],
            (string) $row['status'],
            (string) $row['expires_at'],
            $row['accepted_at'] !== null ? (string) $row['accepted_at'] : null,
        );
    }
}
