<?php

declare(strict_types=1);

namespace NeneServe\Tenant;

final class InMemoryInvitationRepository implements InvitationRepositoryInterface
{
    /** @var array<string, Invitation> keyed by id */
    private array $byId = [];

    public function findByTokenHash(string $tokenHash): ?Invitation
    {
        foreach ($this->byId as $invitation) {
            if (hash_equals($invitation->tokenHash, $tokenHash)) {
                return $invitation;
            }
        }

        return null;
    }

    public function save(Invitation $invitation): void
    {
        $this->byId[$invitation->id] = $invitation;
    }
}
