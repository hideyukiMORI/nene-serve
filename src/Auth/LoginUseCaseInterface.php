<?php

declare(strict_types=1);

namespace NeneServe\Auth;

use NeneServe\Tenant\UseCase\AuthenticationFailedException;

interface LoginUseCaseInterface
{
    /**
     * @throws AuthenticationFailedException when the organization, account, or
     *                                       password is invalid (no enumeration).
     */
    public function execute(LoginInput $input): LoginOutput;
}
