<?php

declare(strict_types=1);

namespace NeneServe\Tenant;

use Nene2\Database\DatabaseQueryExecutorInterface;

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
            'INSERT INTO users (id, organization_id, email, password_hash, role, status)
             VALUES (:id, :org, :email, :password_hash, :role, :status) AS new
             ON DUPLICATE KEY UPDATE
                email = new.email, password_hash = new.password_hash,
                role = new.role, status = new.status',
            [
                ':id' => $user->id,
                ':org' => $user->organizationId,
                ':email' => $user->email,
                ':password_hash' => $user->passwordHash,
                ':role' => $user->role->value,
                ':status' => $user->status,
            ],
        );
    }

    public function listByOrganization(string $organizationId): array
    {
        $rows = $this->query->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM users WHERE organization_id = ? ORDER BY email',
            [$organizationId],
        );

        return $this->hydrateMany($rows);
    }

    public function findByIdAcrossTenants(string $userId): ?User
    {
        $row = $this->query->fetchOne('SELECT ' . self::COLUMNS . ' FROM users WHERE id = ? LIMIT 1', [$userId]);

        return $row === null ? null : $this->hydrate($row);
    }

    public function listAll(): array
    {
        $rows = $this->query->fetchAll('SELECT ' . self::COLUMNS . ' FROM users ORDER BY organization_id, email');

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
