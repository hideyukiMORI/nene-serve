<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Invitations;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Tenant\Invitation;
use NeneServe\Tenant\PdoInvitationRepository;
use NeneServe\Tenant\PdoUserRepository;
use NeneServe\Tenant\UseCase\InvitationInvalidException;
use NeneServe\Tenant\UseCase\UserValidationException;
use NeneServe\Tenant\User;

/**
 * Unauthenticated invitation acceptance: validate the single-use, unexpired
 * token and set the invitee's password. Atomic + audited (NENE2 transaction
 * pattern). A non-acceptable token is reported uniformly to avoid enumeration.
 */
final readonly class AcceptInvitationUseCase implements AcceptInvitationUseCaseInterface
{
    private const MIN_PASSWORD_LENGTH = 8;

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private DatabaseTransactionManagerInterface $transactions,
    ) {
    }

    public function execute(string $rawToken, string $password): User
    {
        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw new UserValidationException('Password must be at least 8 characters.');
        }

        $now = gmdate('Y-m-d H:i:s');
        $invitation = (new PdoInvitationRepository($this->query))->findByTokenHash(Invitation::hash($rawToken));

        if ($invitation === null || !$invitation->isAcceptable($now)) {
            throw new InvitationInvalidException('Invitation is invalid, used, or expired.');
        }

        $user = (new PdoUserRepository($this->query))->findByIdAcrossTenants($invitation->userId);

        if ($user === null) {
            throw new InvitationInvalidException('Invitation is invalid.');
        }

        $updated = $user->withPasswordHash(password_hash($password, PASSWORD_DEFAULT));
        $accepted = $invitation->accepted($now);

        return $this->transactions->transactional(
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
    }

    public function preview(string $rawToken): ?User
    {
        $now = gmdate('Y-m-d H:i:s');
        $invitation = (new PdoInvitationRepository($this->query))->findByTokenHash(Invitation::hash($rawToken));

        if ($invitation === null || !$invitation->isAcceptable($now)) {
            return null;
        }

        return (new PdoUserRepository($this->query))->findByIdAcrossTenants($invitation->userId);
    }
}
