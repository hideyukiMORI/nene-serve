<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Invitations;

use NeneServe\Tenant\UseCase\InvitationInvalidException;
use NeneServe\Tenant\UseCase\UserValidationException;
use NeneServe\Tenant\User;

interface AcceptInvitationUseCaseInterface
{
    /**
     * @return User the now-active user
     *
     * @throws UserValidationException   when the password is too weak
     * @throws InvitationInvalidException when the token is unknown / used / expired
     */
    public function execute(string $rawToken, string $password): User;

    /** Returns the invited user for a valid token, or null otherwise. */
    public function preview(string $rawToken): ?User;
}
