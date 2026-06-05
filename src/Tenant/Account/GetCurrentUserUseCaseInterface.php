<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Account;

interface GetCurrentUserUseCaseInterface
{
    public function execute(GetCurrentUserInput $input): GetCurrentUserOutput;
}
