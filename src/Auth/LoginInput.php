<?php

declare(strict_types=1);

namespace NeneServe\Auth;

final readonly class LoginInput
{
    public function __construct(
        public string $organization,
        public string $email,
        public string $password,
    ) {
    }
}
