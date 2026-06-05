<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Users;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Tenant\Invitation;
use NeneServe\Tenant\PdoInvitationRepository;
use NeneServe\Tenant\PdoUserRepository;
use NeneServe\Tenant\Role;
use NeneServe\Tenant\UseCase\InvitedUser;
use NeneServe\Tenant\UseCase\UserValidationException;
use NeneServe\Tenant\User;
use NeneServe\Tenant\UserRepositoryInterface;

/**
 * Creates an operator account in invited state (no usable password) plus a
 * single-use invitation token, atomically and audited. The raw token is
 * returned for the email link; only its hash is persisted.
 *
 * Follows the NENE2 transaction pattern: repositories and the audit log are
 * constructed with the transaction-bound executor inside `transactional()`.
 */
final readonly class CreateInvitedUserUseCase implements CreateInvitedUserUseCaseInterface
{
    private const TOKEN_TTL_HOURS = 72;

    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private UserRepositoryInterface $users,
        private DatabaseTransactionManagerInterface $transactions,
        private RequestScopedHolder $organizationId,
    ) {
    }

    public function execute(CreateInvitedUserInput $input): InvitedUser
    {
        $email = trim($input->email);

        if ($email === '' || !str_contains($email, '@')) {
            throw new UserValidationException('A valid email is required.');
        }

        $parsedRole = Role::tryFrom($input->role);

        if ($parsedRole === null) {
            throw new UserValidationException('Unknown role.');
        }

        $organizationId = $this->organizationId->get();

        if ($this->users->findByEmailInOrganization($email, $organizationId) !== null) {
            throw new UserValidationException('A user with that email already exists.');
        }

        $user = new User(
            'usr-' . bin2hex(random_bytes(8)),
            $organizationId,
            $email,
            $parsedRole,
            '', // no usable password until the invitation is accepted
            'active',
        );
        $rawToken = bin2hex(random_bytes(32));
        $invitation = new Invitation(
            'inv-' . bin2hex(random_bytes(8)),
            $organizationId,
            $user->id,
            Invitation::hash($rawToken),
            'pending',
            gmdate('Y-m-d H:i:s', time() + self::TOKEN_TTL_HOURS * 3600),
        );

        $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($user, $invitation, $input, $organizationId): void {
                (new PdoUserRepository($tx))->save($user);
                (new PdoInvitationRepository($tx))->save($invitation);

                $audit = new PdoAuditLog($tx);
                $audit->record(
                    $organizationId,
                    $input->actorUserId,
                    'user.created',
                    'user',
                    $user->id,
                    ['after' => ['email' => $user->email, 'role' => $user->role->value]],
                );
                $audit->record(
                    $organizationId,
                    $input->actorUserId,
                    'invitation.sent',
                    'invitation',
                    $invitation->id,
                    ['user_id' => $user->id],
                );
            },
        );

        return new InvitedUser($user, $rawToken);
    }
}
