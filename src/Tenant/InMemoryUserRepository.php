<?php

declare(strict_types=1);

namespace NeneServe\Tenant;

/**
 * In-memory user store. Backs unit tests and local `php -S` boot before the
 * MySQL stack is provisioned; {@see PdoUserRepository} is the production store.
 * Both enforce the same tenant-scoping contract.
 */
final class InMemoryUserRepository implements UserRepositoryInterface
{
    /** @var list<User> */
    private array $users;

    /** @param list<User> $users */
    public function __construct(array $users = [])
    {
        $this->users = $users;
    }

    public function findByIdInOrganization(string $userId, string $organizationId): ?User
    {
        foreach ($this->users as $user) {
            if ($user->id === $userId && $user->organizationId === $organizationId) {
                return $user;
            }
        }

        return null;
    }

    public function findByEmailInOrganization(string $email, string $organizationId): ?User
    {
        foreach ($this->users as $user) {
            if ($user->email === $email && $user->organizationId === $organizationId) {
                return $user;
            }
        }

        return null;
    }

    public function listByOrganization(string $organizationId): array
    {
        return array_values(array_filter(
            $this->users,
            static fn (User $u): bool => $u->organizationId === $organizationId,
        ));
    }

    public function findByIdAcrossTenants(string $userId): ?User
    {
        foreach ($this->users as $user) {
            if ($user->id === $userId) {
                return $user;
            }
        }

        return null;
    }

    public function listAll(): array
    {
        return $this->users;
    }
}
