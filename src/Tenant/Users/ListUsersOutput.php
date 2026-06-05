<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Users;

use NeneServe\Tenant\User;

final readonly class ListUsersOutput
{
    /** @param list<User> $items */
    public function __construct(
        public array $items,
        public int $limit,
        public int $offset,
    ) {
    }
}
