<?php

declare(strict_types=1);

namespace NeneServe\Tenant\UseCase;

use NeneServe\Tenant\User;

/** Result of creating an invited user: the user plus the raw token for the email link. */
final class InvitedUser
{
    public function __construct(
        public readonly User $user,
        public readonly string $rawToken,
    ) {
    }
}
