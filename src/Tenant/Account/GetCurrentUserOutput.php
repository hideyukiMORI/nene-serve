<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Account;

use NeneServe\Tenant\User;

final readonly class GetCurrentUserOutput
{
    public function __construct(
        public ?User $user,
    ) {
    }
}
