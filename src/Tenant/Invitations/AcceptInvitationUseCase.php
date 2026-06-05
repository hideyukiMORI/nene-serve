<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Invitations;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\SecureTokenHelper;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Tenant\InvitationRepositoryInterface;
use NeneServe\Tenant\PdoInvitationRepository;
use NeneServe\Tenant\PdoUserRepository;
use NeneServe\Tenant\UseCase\InvitationInvalidException;
use NeneServe\Tenant\UseCase\UserValidationException;
use NeneServe\Tenant\User;
use NeneServe\Tenant\UserRepositoryInterface;

/**
 * Unauthenticated invitation acceptance: validate the single-use, unexpired
 * token and set the invitee's password. Atomic + audited (NENE2 transaction
 * pattern). A non-acceptable token is reported uniformly to avoid enumeration.
 */
final readonly class AcceptInvitationUseCase implements AcceptInvitationUseCaseInterface
{
    private const MIN_PASSWORD_LENGTH = 8;

    public function __construct(
        private InvitationRepositoryInterface $invitations,
        private UserRepositoryInterface $users,
        private DatabaseTransactionManagerInterface $transactions,
    ) {
    }

    public function execute(AcceptInvitationInput $input): AcceptInvitationOutput
    {
        if (strlen($input->password) < self::MIN_PASSWORD_LENGTH) {
            throw new UserValidationException('Password must be at least 8 characters.');
        }

        $now = gmdate('Y-m-d H:i:s');
        $invitation = $this->invitations->findByTokenHash(SecureTokenHelper::hash($input->rawToken));

        if ($invitation === null || !$invitation->isAcceptable($now)) {
            throw new InvitationInvalidException('Invitation is invalid, used, or expired.');
        }

        $user = $this->users->findByIdAcrossTenants($invitation->userId);

        if ($user === null) {
            throw new InvitationInvalidException('Invitation is invalid.');
        }

        $updated = $user->withPasswordHash(password_hash($input->password, PASSWORD_DEFAULT));
        $accepted = $invitation->accepted($now);

        $stored = $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($updated, $accepted, $invitation): User {
                (new PdoUserRepository($tx))->save($updated);
                (new PdoInvitationRepository($tx))->save($accepted);
                (new PdoAuditLog($tx))->record(
                    $accepted->organizationId,
                    $updated->id,
                    'invitation.accepted',
                    'invitation',
                    $invitation->id,
                    ['user_id' => $updated->id],
                );

                return $updated;
            },
        );

        return new AcceptInvitationOutput($stored);
    }

    public function preview(PreviewInvitationInput $input): PreviewInvitationOutput
    {
        $now = gmdate('Y-m-d H:i:s');
        $invitation = $this->invitations->findByTokenHash(SecureTokenHelper::hash($input->rawToken));

        if ($invitation === null || !$invitation->isAcceptable($now)) {
            return new PreviewInvitationOutput(null);
        }

        return new PreviewInvitationOutput($this->users->findByIdAcrossTenants($invitation->userId));
    }
}
