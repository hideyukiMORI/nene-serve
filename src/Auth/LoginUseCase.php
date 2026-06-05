<?php

declare(strict_types=1);

namespace NeneServe\Auth;

use Nene2\Auth\TokenIssuerInterface;
use NeneServe\Tenant\OrganizationRepositoryInterface;
use NeneServe\Tenant\UseCase\AuthenticationFailedException;
use NeneServe\Tenant\UserRepositoryInterface;

/**
 * Authenticates an operator within an organization and issues an admin bearer
 * token via the framework token issuer (ADR 0006 / ADR 0018). The organization
 * is part of the credential and every failure mode raises the same
 * {@see AuthenticationFailedException} (no account enumeration).
 */
final readonly class LoginUseCase implements LoginUseCaseInterface
{
    public function __construct(
        private OrganizationRepositoryInterface $organizations,
        private UserRepositoryInterface $users,
        private TokenIssuerInterface $tokenIssuer,
        private int $ttlSeconds = 3600,
    ) {
    }

    public function execute(LoginInput $input): LoginOutput
    {
        $organization = $this->organizations->findBySlug($input->organization);

        if ($organization === null || !$organization->isActive()) {
            throw new AuthenticationFailedException();
        }

        $user = $this->users->findByEmailInOrganization($input->email, $organization->id);

        if ($user === null || !$user->isActive() || !$user->verifyPassword($input->password)) {
            throw new AuthenticationFailedException();
        }

        $now = time();
        $token = $this->tokenIssuer->issue([
            'sub' => $user->id,
            'org' => $user->organizationId,
            'role' => $user->role->value,
            'iat' => $now,
            'exp' => $now + $this->ttlSeconds,
        ]);

        return new LoginOutput($token, $user->toPublicArray());
    }
}
