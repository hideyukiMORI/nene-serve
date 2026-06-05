<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Invitations;

final readonly class PreviewInvitationInput
{
    public function __construct(
        public string $rawToken,
    ) {
    }
}
