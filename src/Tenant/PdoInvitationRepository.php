<?php

declare(strict_types=1);

namespace NeneServe\Tenant;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeneServe\Support\SqlDialect;

final readonly class PdoInvitationRepository implements InvitationRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, user_id, token_hash, status, expires_at, accepted_at';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private SqlDialect $dialect = SqlDialect::Mysql,
    ) {
    }

    public function findByTokenHash(string $tokenHash): ?Invitation
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::COLUMNS . ' FROM invitations WHERE token_hash = ? LIMIT 1',
            [$tokenHash],
        );

        return $row === null ? null : $this->hydrate($row);
    }

    public function save(Invitation $invitation): void
    {
        $this->query->execute(
            $this->dialect->upsert(
                'invitations',
                ['id', 'organization_id', 'user_id', 'token_hash', 'status', 'expires_at', 'accepted_at'],
                ['id'],
                ['status', 'accepted_at'],
            ),
            [
                $invitation->id,
                $invitation->organizationId,
                $invitation->userId,
                $invitation->tokenHash,
                $invitation->status,
                $invitation->expiresAt,
                $invitation->acceptedAt,
            ],
        );
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
