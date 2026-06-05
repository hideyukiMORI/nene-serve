<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Users;

final readonly class ListUsersInput
{
    public function __construct(
        public bool $crossTenant = false,
        public int $limit = 20,
        public int $offset = 0,
    ) {
    }
}
