<?php

declare(strict_types=1);

namespace NeneServe\Tenant;

use PDO;

/**
 * Production user store. Every tenant-scoped query carries
 * `WHERE organization_id = ?` so cross-tenant rows are unreachable through the
 * scoped methods (ADR 0006). The cross-tenant methods omit that clause and are
 * for superadmin callers only.
 */
final class PdoUserRepository implements UserRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, email, role, password_hash, status';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function findByIdInOrganization(string $userId, string $organizationId): ?User
    {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::COLUMNS . ' FROM users WHERE id = ? AND organization_id = ? LIMIT 1',
        );
        $stmt->execute([$userId, $organizationId]);

        return $this->hydrateOne($stmt->fetch());
    }

    public function findByEmailInOrganization(string $email, string $organizationId): ?User
    {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::COLUMNS . ' FROM users WHERE email = ? AND organization_id = ? LIMIT 1',
        );
        $stmt->execute([$email, $organizationId]);

        return $this->hydrateOne($stmt->fetch());
    }

    public function save(User $user): void
    {
        // Upsert without DELETE privilege (app role has none): INSERT .. ON
        // DUPLICATE KEY UPDATE. Email/role/password/status may change; org is fixed.
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (id, organization_id, email, password_hash, role, status)
             VALUES (:id, :org, :email, :password_hash, :role, :status) AS new
             ON DUPLICATE KEY UPDATE
                email = new.email, password_hash = new.password_hash,
                role = new.role, status = new.status',
        );
        $stmt->execute([
            ':id' => $user->id,
            ':org' => $user->organizationId,
            ':email' => $user->email,
            ':password_hash' => $user->passwordHash,
            ':role' => $user->role->value,
            ':status' => $user->status,
        ]);
    }

    public function listByOrganization(string $organizationId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::COLUMNS . ' FROM users WHERE organization_id = ? ORDER BY email',
        );
        $stmt->execute([$organizationId]);

        return $this->hydrateMany($stmt->fetchAll());
    }

    public function findByIdAcrossTenants(string $userId): ?User
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);

        return $this->hydrateOne($stmt->fetch());
    }

    public function listAll(): array
    {
        $stmt = $this->pdo->query('SELECT ' . self::COLUMNS . ' FROM users ORDER BY organization_id, email');

        return $this->hydrateMany($stmt === false ? [] : $stmt->fetchAll());
    }

    /** @param array<string, mixed>|false $row */
    private function hydrateOne(array|false $row): ?User
    {
        return $row === false ? null : $this->hydrate($row);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return list<User>
     */
    private function hydrateMany(array $rows): array
    {
        return array_map($this->hydrate(...), array_values($rows));
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
