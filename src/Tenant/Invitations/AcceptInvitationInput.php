<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Invitations;

final readonly class AcceptInvitationInput
{
    public function __construct(
        public string $rawToken,
        public string $password,
    ) {
    }
}
