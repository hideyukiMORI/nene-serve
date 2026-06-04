<?php

declare(strict_types=1);

namespace NeneServe\Tenant\UseCase;

use NeneServe\Audit\AuditLogInterface;
use NeneServe\Support\TransactionManagerInterface;
use NeneServe\Tenant\Invitation;
use NeneServe\Tenant\InvitationRepositoryInterface;
use NeneServe\Tenant\User;
use NeneServe\Tenant\UserRepositoryInterface;

/**
 * Unauthenticated invitation acceptance: validate the single-use, unexpired
 * token and set the invitee's password. Atomic + audited. A non-acceptable token
 * (unknown / used / expired) is reported uniformly to avoid enumeration.
 */
final class AcceptInvitationUseCase
{
    private const MIN_PASSWORD_LENGTH = 8;

    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly InvitationRepositoryInterface $invitations,
        private readonly AuditLogInterface $audit,
        private readonly TransactionManagerInterface $tx,
    ) {
    }

    /** @return User the now-active user */
    public function execute(string $rawToken, string $password): User
    {
        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw new UserValidationException('Password must be at least 8 characters.');
        }

        $now = gmdate('Y-m-d H:i:s');
        $invitation = $this->invitations->findByTokenHash(Invitation::hash($rawToken));
        if ($invitation === null || !$invitation->isAcceptable($now)) {
            throw new InvitationInvalidException('Invitation is invalid, used, or expired.');
        }

        $user = $this->users->findByIdAcrossTenants($invitation->userId);
        if ($user === null) {
            throw new InvitationInvalidException('Invitation is invalid.');
        }

        $updated = $user->withPasswordHash(password_hash($password, PASSWORD_DEFAULT));
        $accepted = $invitation->accepted($now);

        return $this->tx->transactional(function () use ($updated, $accepted, $invitation): User {
            $this->users->save($updated);
            $this->invitations->save($accepted);
            $this->audit->record(
                $accepted->organizationId,
                $updated->id,
                'invitation.accepted',
                'invitation',
                $invitation->id,
                ['user_id' => $updated->id],
            );

            return $updated;
        });
    }

    public function preview(string $rawToken): ?User
    {
        $now = gmdate('Y-m-d H:i:s');
        $invitation = $this->invitations->findByTokenHash(Invitation::hash($rawToken));
        if ($invitation === null || !$invitation->isAcceptable($now)) {
            return null;
        }

        return $this->users->findByIdAcrossTenants($invitation->userId);
    }
}
