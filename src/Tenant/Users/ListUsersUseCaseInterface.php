<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Users;

interface ListUsersUseCaseInterface
{
    public function execute(ListUsersInput $input): ListUsersOutput;
}
