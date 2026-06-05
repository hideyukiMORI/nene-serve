<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Account;

final readonly class GetCurrentUserInput
{
    public function __construct(
        public string $userId,
    ) {
    }
}
