<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Users;

use NeneServe\Tenant\UseCase\InvitedUser;
use NeneServe\Tenant\UseCase\UserValidationException;

interface CreateInvitedUserUseCaseInterface
{
    /**
     * @throws UserValidationException when the email/role is invalid or the email already exists.
     */
    public function execute(CreateInvitedUserInput $input): InvitedUser;
}
