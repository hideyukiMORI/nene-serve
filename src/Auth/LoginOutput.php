<?php

declare(strict_types=1);

namespace NeneServe\Auth;

final readonly class LoginOutput
{
    /**
     * @param array{id: string, organization_id: string, email: string, role: string} $user
     */
    public function __construct(
        public string $token,
        public array $user,
    ) {
    }
}
