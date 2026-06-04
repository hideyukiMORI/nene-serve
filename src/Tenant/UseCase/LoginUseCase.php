<?php

declare(strict_types=1);

namespace NeneServe\Tenant\UseCase;

use NeneServe\Http\Auth\Jwt;
use NeneServe\Tenant\OrganizationRepositoryInterface;
use NeneServe\Tenant\User;
use NeneServe\Tenant\UserRepositoryInterface;

/**
 * Authenticates an operator within an organization and issues an admin JWT.
 *
 * The organization is part of the credential: a user is resolved only inside
 * the named tenant (ADR 0006). All failure modes raise the same
 * {@see AuthenticationFailedException} (no account enumeration).
 */
final class LoginUseCase
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizations,
        private readonly UserRepositoryInterface $users,
        private readonly Jwt $jwt,
        private readonly int $ttlSeconds = 3600,
    ) {
    }

    /**
     * @return array{token: string, user: array{id: string, organization_id: string, email: string, role: string}}
     */
    public function execute(string $organizationSlug, string $email, string $password): array
    {
        $organization = $this->organizations->findBySlug($organizationSlug);
        if ($organization === null || !$organization->isActive()) {
            throw new AuthenticationFailedException();
        }

        $user = $this->users->findByEmailInOrganization($email, $organization->id);
        if ($user === null || !$user->isActive() || !$user->verifyPassword($password)) {
            throw new AuthenticationFailedException();
        }

        $token = $this->jwt->issue([
            'sub' => $user->id,
            'org' => $user->organizationId,
            'role' => $user->role->value,
        ], $this->ttlSeconds);

        return ['token' => $token, 'user' => $this->projectUser($user)];
    }

    /**
     * @return array{id: string, organization_id: string, email: string, role: string}
     */
    private function projectUser(User $user): array
    {
        return $user->toPublicArray();
    }
}
