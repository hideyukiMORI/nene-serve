<?php

declare(strict_types=1);

namespace NeneServe\Tenant;

interface InvitationRepositoryInterface
{
    /** Resolve by token hash (unauthenticated accept path — the token is the secret). */
    public function findByTokenHash(string $tokenHash): ?Invitation;

    public function save(Invitation $invitation): void;
}
