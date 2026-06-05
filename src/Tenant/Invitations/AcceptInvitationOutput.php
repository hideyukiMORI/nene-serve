<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Invitations;

use NeneServe\Tenant\User;

final readonly class AcceptInvitationOutput
{
    public function __construct(
        public User $user,
    ) {
    }
}
