<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Invitations;

use NeneServe\Tenant\UseCase\InvitationInvalidException;
use NeneServe\Tenant\UseCase\UserValidationException;

interface AcceptInvitationUseCaseInterface
{
    /**
     * @throws UserValidationException    when the password is too weak
     * @throws InvitationInvalidException when the token is unknown / used / expired
     */
    public function execute(AcceptInvitationInput $input): AcceptInvitationOutput;

    /** Returns the invited user for a valid token, or a null user otherwise. */
    public function preview(PreviewInvitationInput $input): PreviewInvitationOutput;
}
