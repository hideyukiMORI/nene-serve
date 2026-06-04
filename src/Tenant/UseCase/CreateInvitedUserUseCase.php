<?php

declare(strict_types=1);

namespace NeneServe\Tenant\UseCase;

use NeneServe\Audit\AuditLogInterface;
use NeneServe\Support\TransactionManagerInterface;
use NeneServe\Tenant\AuthContext;
use NeneServe\Tenant\Invitation;
use NeneServe\Tenant\InvitationRepositoryInterface;
use NeneServe\Tenant\Role;
use NeneServe\Tenant\User;
use NeneServe\Tenant\UserRepositoryInterface;

/**
 * Creates an operator account in `invited` state (no usable password) plus a
 * single-use invitation token. Atomic + audited. The raw token is returned for
 * the email link; only its hash is persisted.
 */
final class CreateInvitedUserUseCase
{
    private const TOKEN_TTL_HOURS = 72;

    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly InvitationRepositoryInterface $invitations,
        private readonly AuditLogInterface $audit,
        private readonly TransactionManagerInterface $tx,
    ) {
    }

    public function execute(AuthContext $actor, string $email, string $role): InvitedUser
    {
        $email = trim($email);
        if ($email === '' || !str_contains($email, '@')) {
            throw new UserValidationException('A valid email is required.');
        }
        $parsedRole = Role::tryFrom($role);
        if ($parsedRole === null) {
            throw new UserValidationException('Unknown role.');
        }
        if ($this->users->findByEmailInOrganization($email, $actor->organizationId) !== null) {
            throw new UserValidationException('A user with that email already exists.');
        }

        $user = new User(
            'usr-' . bin2hex(random_bytes(8)),
            $actor->organizationId,
            $email,
            $parsedRole,
            '', // no usable password until the invitation is accepted
            'active',
        );
        $rawToken = bin2hex(random_bytes(32));
        $invitation = new Invitation(
            'inv-' . bin2hex(random_bytes(8)),
            $actor->organizationId,
            $user->id,
            Invitation::hash($rawToken),
            'pending',
            gmdate('Y-m-d H:i:s', time() + self::TOKEN_TTL_HOURS * 3600),
        );

        $this->tx->transactional(function () use ($user, $invitation, $actor): void {
            $this->users->save($user);
            $this->invitations->save($invitation);
            $this->audit->record(
                $actor->organizationId,
                $actor->userId,
                'user.created',
                'user',
                $user->id,
                ['after' => ['email' => $user->email, 'role' => $user->role->value]],
            );
            $this->audit->record(
                $actor->organizationId,
                $actor->userId,
                'invitation.sent',
                'invitation',
                $invitation->id,
                ['user_id' => $user->id],
            );
        });

        return new InvitedUser($user, $rawToken);
    }
}
