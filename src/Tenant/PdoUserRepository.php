<?php

declare(strict_types=1);

namespace NeneServe\Tenant;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeneServe\Support\SqlDialect;

/**
 * Production user store on the NENE2 query executor. Every tenant-scoped query
 * carries `WHERE organization_id = ?` so cross-tenant rows are unreachable
 * through the scoped methods (ADR 0006). The cross-tenant methods omit that
 * clause and are for superadmin callers only.
 */
final readonly class PdoUserRepository implements UserRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, email, role, password_hash, status';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private SqlDialect $dialect = SqlDialect::Mysql,
    ) {
    }

    public function findByIdInOrganization(string $userId, string $organizationId): ?User
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::COLUMNS . ' FROM users WHERE id = ? AND organization_id = ? LIMIT 1',
            [$userId, $organizationId],
        );

        return $row === null ? null : $this->hydrate($row);
    }

    public function findByEmailInOrganization(string $email, string $organizationId): ?User
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::COLUMNS . ' FROM users WHERE email = ? AND organization_id = ? LIMIT 1',
            [$email, $organizationId],
        );

        return $row === null ? null : $this->hydrate($row);
    }

    public function save(User $user): void
    {
        // Upsert without DELETE privilege (app role has none): INSERT .. ON
        // DUPLICATE KEY UPDATE. Email/role/password/status may change; org is fixed.
        $this->query->execute(
            $this->dialect->upsert(
                'users',
                ['id', 'organization_id', 'email', 'password_hash', 'role', 'status'],
                ['id'],
                ['email', 'password_hash', 'role', 'status'],
            ),
            [
                $user->id,
                $user->organizationId,
                $user->email,
                $user->passwordHash,
                $user->role->value,
                $user->status,
            ],
        );
    }

    public function listByOrganization(string $organizationId, int $limit, int $offset): array
    {
        $rows = $this->query->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM users WHERE organization_id = ? ORDER BY email LIMIT ? OFFSET ?',
            [$organizationId, $limit, $offset],
        );

        return $this->hydrateMany($rows);
    }

    public function findByIdAcrossTenants(string $userId): ?User
    {
        $row = $this->query->fetchOne('SELECT ' . self::COLUMNS . ' FROM users WHERE id = ? LIMIT 1', [$userId]);

        return $row === null ? null : $this->hydrate($row);
    }

    public function listAll(int $limit, int $offset): array
    {
        $rows = $this->query->fetchAll('SELECT ' . self::COLUMNS . ' FROM users ORDER BY organization_id, email LIMIT ? OFFSET ?', [$limit, $offset]);

        return $this->hydrateMany($rows);
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<User>
     */
    private function hydrateMany(array $rows): array
    {
        return array_map($this->hydrate(...), $rows);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): User
    {
        return new User(
            (string) $row['id'],
            (string) $row['organization_id'],
            (string) $row['email'],
            Role::from((string) $row['role']),
            (string) $row['password_hash'],
            (string) $row['status'],
        );
    }
}
