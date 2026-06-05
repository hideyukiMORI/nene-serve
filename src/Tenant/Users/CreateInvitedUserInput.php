<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Users;

final readonly class CreateInvitedUserInput
{
    public function __construct(
        public string $actorUserId,
        public string $email,
        public string $role,
    ) {
    }
}
